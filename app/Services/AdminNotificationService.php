<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\ContactMessage;
use App\Models\Proposal;
use App\Models\ProposalComment;
use App\Models\SupportTicket;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class AdminNotificationService
{
    public static function notifications(int $limit = 500, bool $unreadOnly = false, ?string $type = null): Collection
    {
        $tickets = collect();

        if ($type === null || $type === 'ticket') {
            $tickets = SupportTicket::query()
                ->with('user')
                ->when($unreadOnly, fn ($query) => $query->whereNull('admin_read_at'))
                ->latest('last_reply_at')
                ->latest()
                ->limit($limit)
                ->get()
                ->toBase()
                ->map(fn ($ticket) => [
                    'type' => 'ticket',
                    'id' => $ticket->id,
                    'title' => 'New ticket update',
                    'subject' => $ticket->subject,
                    'from' => $ticket->customer_name ?? ($ticket->user?->name ?? 'Customer'),
                    'priority' => $ticket->priority,
                    'time' => $ticket->last_reply_at ?? $ticket->created_at,
                    'read' => $ticket->admin_read_at !== null,
                    'url' => Route::has('admin.tickets.show') ? route('admin.tickets.show', $ticket) : '#',
                ]);
        }

        $contacts = collect();

        if ($type === null || $type === 'contact') {
            $contacts = ContactMessage::query()
                ->when($unreadOnly, fn ($query) => $query->whereNull('admin_read_at'))
                ->latest()
                ->limit($limit)
                ->get()
                ->toBase()
                ->map(fn ($message) => [
                    'type' => 'contact',
                    'id' => $message->id,
                    'title' => 'New contact message',
                    'subject' => $message->subject,
                    'from' => $message->name,
                    'priority' => 'new',
                    'time' => $message->created_at,
                    'read' => $message->admin_read_at !== null,
                    'url' => Route::has('admin.contact-messages.index') ? route('admin.contact-messages.index') : '#',
                ]);
        }

        $bookings = collect();

        if ($type === null || $type === 'booking') {
            $bookings = Booking::query()
                ->with(['user', 'service', 'servicePlan', 'pricingPlan'])
                ->when($unreadOnly, fn ($query) => $query->whereNull('admin_read_at'))
                ->latest()
                ->limit($limit)
                ->get()
                ->toBase()
                ->map(fn ($booking) => [
                    'type' => 'booking',
                    'id' => $booking->id,
                    'title' => $booking->booking_type === 'pricing_plan' ? 'New plan booking' : 'New service booking',
                    'subject' => ($booking->booking_no ?? 'Booking').' · '.static::bookingTitle($booking),
                    'from' => $booking->full_name ?? ($booking->user?->name ?? 'Customer'),
                    'priority' => $booking->status ?? 'pending',
                    'time' => $booking->created_at,
                    'read' => $booking->admin_read_at !== null,
                    'url' => Route::has('admin.bookings.quote') ? route('admin.bookings.quote', $booking) : (Route::has('admin.bookings.index') ? route('admin.bookings.index') : '#'),
                ]);
        }

        $proposalComments = collect();

        if ($type === null || $type === 'proposal') {
            $proposalComments = ProposalComment::query()
                ->with('proposal')
                ->when($unreadOnly, fn ($query) => $query->whereNull('admin_read_at'))
                ->latest()
                ->limit($limit)
                ->get()
                ->toBase()
                ->map(fn ($comment) => [
                    'type' => 'proposal',
                    'id' => $comment->id,
                    'title' => 'New proposal comment',
                    'subject' => $comment->proposal?->subject ?? 'Proposal comment',
                    'from' => $comment->author === 'admin' ? 'Admin' : 'Customer',
                    'priority' => 'new',
                    'time' => $comment->created_at,
                    'read' => $comment->admin_read_at !== null,
                    'url' => Route::has('admin.proposals.view')
                        ? route('admin.proposals.view', $comment->proposal_id).'?comment=1'
                        : '#',
                ]);

            $proposalStatus = Proposal::query()
                ->whereIn('status', ['accepted', 'rejected'])
                ->when($unreadOnly, fn ($query) => $query->whereNull('admin_read_at'))
                ->latest('updated_at')
                ->limit($limit)
                ->get()
                ->toBase()
                ->map(fn ($proposal) => [
                    'type' => 'proposal',
                    'id' => 'status-'.$proposal->id,
                    'title' => $proposal->status === 'accepted' ? 'Proposal accepted' : 'Proposal declined',
                    'subject' => $proposal->subject,
                    'from' => $proposal->customer_name ?? 'Customer',
                    'priority' => 'new',
                    'time' => $proposal->updated_at,
                    'read' => $proposal->admin_read_at !== null,
                    'url' => Route::has('admin.proposals.view')
                        ? route('admin.proposals.view', $proposal->id)
                        : '#',
                ]);

            $proposalComments = $proposalComments->merge($proposalStatus);
        }

        return $tickets
            ->merge($contacts)
            ->merge($bookings)
            ->merge($proposalComments)
            ->sortByDesc('time')
            ->values()
            ->take($limit);
    }

    public static function bookingTitle($booking): string
    {
        if ($booking->booking_type === 'pricing_plan') {
            return $booking->pricingPlan?->title ?? ($booking->plan_name ?? 'Pricing Plan');
        }

        return $booking->service?->card_title ?? ($booking->servicePlan?->name ?? ($booking->plan_name ?? 'Service'));
    }
}
