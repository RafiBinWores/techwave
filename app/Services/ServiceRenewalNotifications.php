<?php

namespace App\Services;

use App\Events\UserNotificationCreated;
use App\Jobs\SendServiceRenewalNotice;
use App\Mail\ServiceRenewalNoticeMail;
use App\Models\Order;
use App\Models\UserNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ServiceRenewalNotifications
{
    public function queue(Order $order, string $kind): bool
    {
        $order = $order->fresh(['user']);
        if (! $order || ! $order->end_date || ! $this->eligible($order, $kind, $order->end_date->toDateString())) {
            return false;
        }
        $period = $order->end_date->toDateString();
        $sent = $order->renewal_notice_deliveries[$period][$kind] ?? [];
        $needsNotification = $order->user !== null && empty($sent['notification_sent_at']);
        $needsEmail = $this->recipient($order) !== null && empty($sent['email_sent_at']);
        if (! $needsNotification && ! $needsEmail) {
            return false;
        }
        SendServiceRenewalNotice::dispatch($order->id, $kind, $period)->afterCommit();

        return true;
    }

    public function deliver(int $orderId, string $kind, string $period): void
    {
        foreach (['notification', 'email'] as $channel) {
            DB::transaction(function () use ($orderId, $kind, $period, $channel): void {
                $candidate = Order::query()->find($orderId);
                if (! $candidate) {
                    return;
                }
                Order::query()->lockForUpdate()->findOrFail($candidate->renewal_of_id ?? $candidate->id);
                $order = Order::query()->lockForUpdate()->with(['user', 'service', 'servicePlan'])->find($orderId);
                if (! $order || ! $this->eligible($order, $kind, $period)) {
                    return;
                }
                $deliveries = $order->renewal_notice_deliveries ?? [];
                $key = $channel.'_sent_at';
                if (! empty($deliveries[$period][$kind][$key])) {
                    return;
                }
                $url = $this->actionUrl($order, $kind);
                if ($channel === 'notification') {
                    if (! $order->user) {
                        return;
                    }
                    $notification = UserNotification::create([
                        'user_id' => $order->user_id, 'type' => 'service-renewal',
                        'title' => $this->title($kind), 'subject' => $this->message($order, $kind),
                        'from' => 'Service Billing', 'url' => $url,
                    ]);
                    DB::afterCommit(fn () => UserNotificationCreated::dispatch($notification));
                } else {
                    $email = $this->recipient($order);
                    if ($email === null) {
                        return;
                    }
                    Mail::to($email)->send(new ServiceRenewalNoticeMail($order, $kind, $url));
                }
                $deliveries[$period][$kind][$key] = now()->toIso8601String();
                DB::table('orders')->where('id', $order->id)->update([
                    'renewal_notice_deliveries' => json_encode($deliveries, JSON_THROW_ON_ERROR),
                ]);
            });
        }
    }

    public function title(string $kind): string
    {
        return match ($kind) {
            'reminder' => 'Your service is due for renewal',
            'expired' => 'Your service has expired',
            'confirmed' => 'Your service renewal is confirmed',
        };
    }

    public function message(Order $order, string $kind): string
    {
        $name = $order->plan_name ?: ($order->servicePlan?->name ?: ($order->service?->card_title ?: 'Your service'));
        $end = $order->end_date->format('d M Y');

        return match ($kind) {
            'reminder' => $name.' expires on '.$end.'. Complete renewal payment to continue your service.',
            'expired' => $name.' expired on '.$end.'. Complete renewal payment to restart your service.',
            'confirmed' => 'Payment confirmed for '.$name.'. Your new service period is '.$order->start_date->format('d M Y').' to '.$end.'.',
        };
    }

    private function eligible(Order $order, string $kind, string $period): bool
    {
        if (! in_array($kind, ['reminder', 'expired', 'confirmed'], true)
            || ! $order->isRenewableService() || $order->end_date->toDateString() !== $period) {
            return false;
        }
        $rootId = $order->renewal_of_id ?? $order->id;
        if (Order::query()->whereKey($rootId)->where('status', 'cancelled')->exists()) {
            return false;
        }
        if ($kind === 'confirmed') {
            return $order->renewal_of_id !== null && $order->renewal_activated_at !== null
                && $order->renewal_paid_at !== null && ($order->renewal_payment_validation_id !== null || $order->renewal_manual_verified_at !== null)
                && $order->start_date !== null;
        }
        $latestId = Order::query()->where(fn ($query) => $query->whereKey($rootId)->orWhere('renewal_of_id', $rootId))
            ->where('payment_status', 'paid')->where('status', '!=', 'cancelled')->whereNotNull('end_date')
            ->orderByDesc('end_date')->orderByDesc('id')->value('id');
        if ($latestId !== $order->id) {
            return false;
        }

        return $kind === 'expired' ? $order->end_date->lt(today())
            : $order->end_date->gte(today()) && $order->end_date->lte(today()->addDays(7));
    }

    private function recipient(Order $order): ?string
    {
        $email = $order->user?->email ?: $order->email;

        return is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function actionUrl(Order $order, string $kind): string
    {
        if ($kind === 'confirmed') {
            return route('client.renewals.checkout', $order);
        }
        $pending = $order->pendingRenewal();

        return $pending ? route('client.renewals.checkout', $pending)
            : route('account.services', ['tab' => 'services']).'#service-order-'.$order->id;
    }
}
