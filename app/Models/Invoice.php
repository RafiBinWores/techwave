<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'user_id',
    'company_id',
    'tool_subscription_id',
    'invoice_no',
    'customer_name',
    'customer_email',
    'customer_phone',
    'company_name',
    'subject',
    'note',
    'terms',
    'discount_type',
    'discount_value',
    'status',
    'issue_date',
    'due_date',
    'sent_at',
])]
class Invoice extends Model
{
    use HasFactory;

    protected $casts = [
        'discount_value' => 'decimal:2',
        'issue_date' => 'date',
        'due_date' => 'date',
        'sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(ToolSubscription::class, 'tool_subscription_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->oldest();
    }

    public static function generateInvoiceNumber(): string
    {
        $datePrefix = 'INV-'.now()->format('Ymd');

        $lastNumber = static::lastSequenceNumber($datePrefix);

        do {
            $lastNumber++;
            $number = str_pad((string) $lastNumber, 4, '0', STR_PAD_LEFT);
            $candidate = $datePrefix.'-'.$number;
        } while (
            static::query()->where('invoice_no', $candidate)->exists()
            || Order::query()->where('order_no', $candidate)->exists()
        );

        return $candidate;
    }

    private static function lastSequenceNumber(string $datePrefix): int
    {
        $invoiceNumber = (int) static::query()
            ->where('invoice_no', 'like', $datePrefix.'-%')
            ->get('invoice_no')
            ->map(fn ($invoice) => (int) Str::afterLast($invoice->invoice_no, '-'))
            ->max();

        $orderNumber = (int) Order::query()
            ->where('order_no', 'like', $datePrefix.'-%')
            ->get('order_no')
            ->map(fn ($order) => (int) Str::afterLast($order->order_no, '-'))
            ->max();

        return max($invoiceNumber, $orderNumber);
    }

    public function subtotal(): float
    {
        return $this->items->sum(fn ($item) => (float) $item->quantity * (float) $item->unit_price);
    }

    public function discountAmount(): float
    {
        $subtotal = $this->subtotal();

        return match ($this->discount_type) {
            'percentage' => ($subtotal * (float) $this->discount_value) / 100,
            'fixed' => (float) $this->discount_value,
            default => 0,
        };
    }

    public function total(): float
    {
        return max($this->subtotal() - $this->discountAmount(), 0);
    }
}
