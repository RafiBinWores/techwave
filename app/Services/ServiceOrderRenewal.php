<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ServiceOrderRenewal
{
    public function request(Order $source): Order
    {
        return DB::transaction(function () use ($source): Order {
            $root = Order::query()->lockForUpdate()->findOrFail($source->renewal_of_id ?? $source->id);
            $source = Order::query()->findOrFail($source->id);

            if (! $source->isRenewableService() || $root->status === 'cancelled') {
                throw ValidationException::withMessages(['renewal' => 'Only paid monthly or yearly services with an end date can be renewed.']);
            }

            $pending = $root->renewals()->where('payment_status', 'awaiting_payment')->where('status', '!=', 'cancelled')->first();
            if ($pending) {
                return $pending;
            }

            if (! $source->canRenew()) {
                throw ValidationException::withMessages(['renewal' => 'Renewal opens seven days before the latest paid service period expires.']);
            }

            $latest = $this->paidPeriods($root)->orderByDesc('end_date')->first() ?? $source;
            $renewal = $latest->replicate();
            $renewal->fill([
                'renewal_of_id' => $root->id,
                'booking_id' => null,
                'order_no' => Invoice::generateInvoiceNumber(),
                'status' => 'pending',
                'payment_status' => 'awaiting_payment',
                'payment_method' => null,
                'start_date' => null,
                'end_date' => null,
                'renewal_activated_at' => null,
                'message' => null,
                'user_note' => 'Renewal of order '.$root->order_no,
                'admin_note' => null,
            ]);
            $renewal->forceFill(['renewal_payment_attempts' => null, 'renewal_payment_validation_id' => null, 'renewal_paid_at' => null, 'renewal_notice_deliveries' => null, 'renewal_sender_bkash' => null, 'renewal_manual_transaction_id' => null, 'renewal_manual_submitted_at' => null, 'renewal_manual_verified_at' => null]);
            $renewal->unsetRelations();
            $renewal->save();

            return $renewal;
        });
    }

    /** @return array<string, mixed> */
    public function paymentAttributes(Order $renewal, string $paymentStatus): array
    {
        $root = Order::query()->lockForUpdate()->findOrFail($renewal->renewal_of_id);
        if ($paymentStatus !== 'paid') {
            if ($renewal->status !== 'cancelled' && $root->renewals()->where('id', '!=', $renewal->id)
                ->where('payment_status', 'awaiting_payment')->where('status', '!=', 'cancelled')->exists()) {
                throw ValidationException::withMessages(['payment_status' => 'Another renewal is already awaiting payment. Resolve it before reversing this payment.']);
            }

            return ['payment_status' => $paymentStatus];
        }
        if ($renewal->status === 'cancelled' || $root->status === 'cancelled') {
            throw ValidationException::withMessages(['payment_status' => 'A cancelled service renewal cannot be paid.']);
        }

        if ($renewal->renewal_activated_at !== null) {
            return ['payment_status' => $paymentStatus];
        }

        if (! in_array($renewal->billing_cycle, ['monthly', 'yearly'], true)) {
            throw ValidationException::withMessages(['billing_cycle' => 'A renewal must have a monthly or yearly billing cycle.']);
        }

        $lastEnd = $this->paidPeriods($root)->where('id', '!=', $renewal->id)->orderByDesc('end_date')->first()?->end_date;
        $start = $lastEnd && $lastEnd->gte(today()) ? $lastEnd->copy()->addDay() : today();
        $end = $renewal->billing_cycle === 'yearly'
            ? $start->copy()->addYearNoOverflow()->subDay()
            : $start->copy()->addMonthNoOverflow()->subDay();

        return [
            'payment_status' => 'paid',
            'status' => 'active',
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'renewal_activated_at' => now(),
        ];
    }

    private function paidPeriods(Order $root): Builder
    {
        return Order::query()->where(fn (Builder $query) => $query->whereKey($root->id)->orWhere('renewal_of_id', $root->id))
            ->where('payment_status', 'paid')->where('status', '!=', 'cancelled')->whereNotNull('end_date');
    }
}
