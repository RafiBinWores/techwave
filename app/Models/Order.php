<?php

namespace App\Models;

use App\Services\Invoices\OrderInvoiceSync;
use App\Services\ServiceOrderRenewal;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

#[Fillable([
    'booking_id',
    'user_id',
    'order_no',
    'order_type',

    'service_id',
    'service_plan_id',
    'pricing_plan_id',

    'billing_cycle',

    'full_name',
    'phone',
    'email',

    'company_name',
    'company_phone',
    'company_email',

    'plan_name',
    'plan_price',
    'requested_price',
    'quoted_price',
    'final_price',
    'amount',
    'currency',

    'message',
    'user_note',
    'admin_note',

    'status',
    'payment_status',
    'payment_method',
    'start_date',
    'end_date',
    'renewal_of_id',
    'renewal_activated_at',
])]
class Order extends Model
{
    protected $attributes = ['payment_status' => 'awaiting_payment'];

    protected $hidden = ['renewal_payment_attempts', 'renewal_notice_deliveries'];

    protected $casts = [
        'plan_price' => 'decimal:2',
        'requested_price' => 'decimal:2',
        'quoted_price' => 'decimal:2',
        'final_price' => 'decimal:2',
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'renewal_activated_at' => 'datetime',
        'renewal_payment_attempts' => 'array',
        'renewal_paid_at' => 'datetime',
        'renewal_manual_submitted_at' => 'datetime',
        'renewal_manual_verified_at' => 'datetime',
        'renewal_notice_deliveries' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (Order $order): void {
            if (($order->order_type ?? 'service') !== 'service') {
                $order->payment_status = in_array($order->status, ['paid', 'active', 'completed'], true) ? 'paid' : 'awaiting_payment';

                return;
            }

            if (in_array($order->status, ['paid', 'awaiting_payment'], true)) {
                $order->payment_status = $order->status;
                $order->status = $order->status === 'paid' ? 'active' : 'pending';
            } elseif (! $order->status) {
                $order->status = 'pending';
            }

            if ($order->renewal_of_id && $order->payment_status === 'paid' && ! $order->renewal_activated_at) {
                throw ValidationException::withMessages([
                    'payment_status' => 'Confirm renewal payment using the payment action so the service period is set correctly.',
                ]);
            }

            if ($order->renewal_of_id && $order->getOriginal('status') === 'cancelled'
                && $order->status !== 'cancelled' && $order->payment_status === 'awaiting_payment') {
                throw ValidationException::withMessages([
                    'status' => 'A cancelled unpaid renewal cannot be reopened. Create a new renewal instead.',
                ]);
            }
        });

        static::saved(function (Order $order): void {
            app(OrderInvoiceSync::class)->sync($order);
            $order->unsetRelation('invoice');
        });
    }

    public function setPaymentStatus(string $status): void
    {
        if (! in_array($status, ['awaiting_payment', 'paid'], true)) {
            throw new InvalidArgumentException('Invalid order payment status.');
        }

        if ($this->order_type === 'pricing_plan') {
            $this->update(['status' => $status]);

            return;
        }

        DB::transaction(function () use ($status): void {
            if ($this->renewal_of_id) {
                static::query()->lockForUpdate()->findOrFail($this->renewal_of_id);
            }
            $order = static::query()->lockForUpdate()->findOrFail($this->id);
            $attributes = $order->renewal_of_id
                ? app(ServiceOrderRenewal::class)->paymentAttributes($order, $status)
                : ['payment_status' => $status];
            $order->update($attributes);
            $this->refresh();
        });
    }

    public function paymentMethodLabel(): string
    {
        return match ($this->payment_method) {
            'sslcommerz' => 'SSLCommerz',
            'manual' => 'Manual bKash',
            default => 'Not recorded',
        };
    }

    public function invoiceItemTitle(): string
    {
        if ($this->order_type === 'pricing_plan') {
            return $this->pricingPlan?->title ?: ($this->plan_name ?: 'Pricing Plan');
        }

        $planName = $this->plan_name ?: $this->servicePlan?->name;
        $optionName = $this->servicePlan?->serviceOption?->card_title;

        if (filled($optionName) && filled($planName)) {
            return $optionName.' - '.$planName;
        }

        return $planName ?: ($optionName ?: ($this->service?->card_title ?: 'Service'));
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function renewalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'renewal_of_id');
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(self::class, 'renewal_of_id');
    }

    public function isRenewableService(): bool
    {
        return $this->order_type === 'service'
            && in_array($this->billing_cycle, ['monthly', 'yearly'], true)
            && $this->payment_status === 'paid'
            && in_array($this->status, ['pending', 'active'], true)
            && $this->end_date !== null;
    }

    public function pendingRenewal(): ?self
    {
        if (! $this->isRenewableService()) {
            return null;
        }

        return static::query()->where('renewal_of_id', $this->renewal_of_id ?? $this->id)
            ->where('payment_status', 'awaiting_payment')->where('status', '!=', 'cancelled')->first();
    }

    public function canRenew(): bool
    {
        if (! $this->isRenewableService() || $this->pendingRenewal()) {
            return false;
        }

        $rootId = $this->renewal_of_id ?? $this->id;
        $latestEnd = static::query()->where(fn ($query) => $query->whereKey($rootId)->orWhere('renewal_of_id', $rootId))
            ->where('payment_status', 'paid')->where('status', '!=', 'cancelled')->max('end_date');

        return $latestEnd !== null && Carbon::parse($latestEnd)->lte(today()->addDays(7));
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function servicePlan()
    {
        return $this->belongsTo(ServicePlan::class);
    }

    public function pricingPlan()
    {
        return $this->belongsTo(PricingPlan::class);
    }

    public function isActive(): bool
    {
        $eligible = $this->order_type === 'service'
            ? $this->payment_status === 'paid' && in_array($this->status, ['pending', 'active'], true)
            : in_array($this->status, ['paid', 'active'], true);

        return $eligible
            && (! $this->start_date || $this->start_date->lte(today()))
            && (! $this->end_date || $this->end_date->gte(today()));
    }

    public function isExpired(): bool
    {
        return $this->end_date && $this->end_date->lt(today());
    }
}
