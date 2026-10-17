<?php

namespace App\Services\Invoices;

use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderInvoiceSync
{
    public function sync(Order $order, bool $onlyMissing = false): Invoice
    {
        $refreshItem = $order->wasChanged([
            'amount', 'final_price', 'quoted_price', 'requested_price', 'plan_price',
            'plan_name', 'service_id', 'service_plan_id', 'pricing_plan_id', 'billing_cycle',
        ]);

        return DB::transaction(function () use ($order, $onlyMissing, $refreshItem) {
            // Serialize synchronization for this order, including concurrent backfills.
            $order = Order::query()->lockForUpdate()->findOrFail($order->getKey());
            $invoice = $order->invoice()->first() ?? new Invoice;

            if ($onlyMissing && $invoice->exists) {
                return $invoice;
            }

            $newInvoice = ! $invoice->exists;
            $title = $order->plan_name
                ?: $order->servicePlan?->name
                ?: $order->pricingPlan?->title
                ?: $order->service?->card_title
                ?: 'Order '.$order->order_no;

            if ($newInvoice) {
                $number = $order->order_no ?: 'ORDER-'.$order->id;
                if (Invoice::query()->where('invoice_no', $number)->exists()) {
                    $number = Invoice::generateInvoiceNumber();
                }

                $invoice->fill([
                    'order_id' => $order->id,
                    'invoice_no' => $number,
                    'issue_date' => $order->created_at?->toDateString() ?? today()->toDateString(),
                    'discount_type' => 'none',
                    'discount_value' => 0,
                    'note' => $order->user_note,
                ]);
            }

            $invoice->fill([
                'user_id' => $order->user_id,
                'customer_name' => $order->full_name ?: ($order->user?->name ?: 'Guest Customer'),
                'customer_email' => $order->email ?: $order->user?->email,
                'customer_phone' => $order->phone ?: $order->user?->phone,
                'company_name' => $order->company_name,
                'subject' => $title,
                'currency' => strtoupper($order->currency ?: 'BDT'),
                'status' => $order->order_type === 'service' ? match (true) {
                    $order->status === 'cancelled' => 'cancelled',
                    $order->payment_status === 'paid' => 'paid',
                    default => 'sent',
                } : match ($order->status) {
                    'paid', 'active', 'completed' => 'paid',
                    'cancelled' => 'cancelled',
                    'pending' => 'draft',
                    default => 'sent',
                },
            ])->save();

            if ($newInvoice || $refreshItem) {
                // The order amount is the final charge, not a subtotal to discount again.
                $invoice->fill(['discount_type' => 'none', 'discount_value' => 0])->save();
                $invoice->items()->delete();
                $invoice->items()->create([
                    'item_type' => $order->order_type === 'pricing_plan' ? 'pricing_plan' : ($order->service_plan_id ? 'service_plan' : ($order->service_id ? 'service' : 'custom')),
                    'item_id' => $order->order_type === 'pricing_plan' ? $order->pricing_plan_id : ($order->service_plan_id ?: $order->service_id),
                    'title' => $title,
                    'description' => $order->billing_cycle ? ucfirst(str_replace('_', ' ', $order->billing_cycle)) : null,
                    'quantity' => 1,
                    'unit_price' => $order->amount ?? $order->final_price ?? $order->quoted_price ?? $order->requested_price ?? $order->plan_price ?? 0,
                ]);
            }

            return $invoice;
        });
    }
}
