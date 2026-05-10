<?php

use App\Events\SupportTicketUpdated;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.admin-app')] #[Title('Support Ticket Chat')] class extends Component {
    use WithFileUploads;

    public SupportTicket $ticket;

    public string $replyMessage = '';
    public string $status = '';
    public string $priority = '';

    public array $images = [];

    public int $refreshKey = 0;

    public function getListeners(): array
    {
        if (!$this->ticket?->id) {
            return [];
        }

        return [
            "echo-private:ticket.{$this->ticket->id},.ticket.updated" => 'refreshTicketFromBroadcast',
        ];
    }

    public function refreshTicketFromBroadcast(): void
    {
        $this->refreshKey++;
        $this->refreshTicket();
        $this->dispatch('ticket-replied');
    }

    public function mount(SupportTicket $ticket): void
    {
        if (is_null($ticket->admin_read_at)) {
            $ticket->update([
                'admin_read_at' => now(),
            ]);
        }

        $this->ticket = $ticket->fresh(['user', 'attachments', 'replies.user', 'replies.attachments']);

        $this->status = $this->ticket->status;
        $this->priority = $this->ticket->priority;
    }

    protected function rules(): array
    {
        return [
            'replyMessage' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'in:open,pending,answered,closed'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'images.*' => ['nullable', 'image', 'max:4096'],
        ];
    }

    public function refreshTicket(): void
    {
        $this->ticket = $this->ticket->fresh(['user', 'attachments', 'replies.user', 'replies.attachments']);

        $this->status = $this->ticket->status;
        $this->priority = $this->ticket->priority;
    }

    public function saveTicketSettings(): void
    {
        $this->validateOnly('status');
        $this->validateOnly('priority');

        $statusChanged = $this->ticket->status !== $this->status;
        $priorityChanged = $this->ticket->priority !== $this->priority;

        $this->ticket->update([
            'status' => $this->status,
            'priority' => $this->priority,
            'closed_at' => $this->status === 'closed' ? now() : null,
            'client_read_at' => $statusChanged || $priorityChanged ? null : $this->ticket->client_read_at,
        ]);

        SupportTicketUpdated::dispatch($this->ticket->fresh(), 'status_changed');

        $this->refreshTicket();

        $this->dispatch('toast', message: 'Ticket settings updated successfully.', type: 'success');
    }

    public function sendReply(): void
    {
        $this->validate();

        if (blank($this->replyMessage) && empty($this->images)) {
            $this->addError('replyMessage', 'Please write a reply or attach an image.');
            return;
        }

        $reply = $this->ticket->replies()->create([
            'user_id' => Auth::id(),
            'sender_type' => 'admin',
            'message' => $this->replyMessage ?: null,
        ]);

        foreach ($this->images as $image) {
            $path = $image->store('support-tickets/' . $this->ticket->id, 'public');

            $reply->attachments()->create([
                'support_ticket_id' => $this->ticket->id,
                'file_name' => $image->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $image->getMimeType(),
                'file_size' => $image->getSize(),
            ]);
        }

        $this->ticket->update([
            'status' => $this->status === 'closed' ? 'closed' : 'answered',
            'last_reply_at' => now(),
            'closed_at' => $this->status === 'closed' ? now() : null,
            'admin_read_at' => now(),
            'client_read_at' => null,
        ]);

        SupportTicketUpdated::dispatch($this->ticket->fresh(), 'admin_replied');

        $this->replyMessage = '';
        $this->images = [];

        $this->refreshTicket();

        $this->dispatch('toast', message: 'Reply sent successfully.', type: 'success');
        $this->dispatch('ticket-replied');
    }

    public function removePreviewImage(int $index): void
    {
        if (isset($this->images[$index])) {
            unset($this->images[$index]);
            $this->images = array_values($this->images);
        }
    }

    public function deleteAttachment(int $attachmentId): void
    {
        $attachment = $this->ticket->attachments()->where('id', $attachmentId)->first();

        if (!$attachment) {
            foreach ($this->ticket->replies as $reply) {
                $attachment = $reply->attachments()->where('id', $attachmentId)->first();

                if ($attachment) {
                    break;
                }
            }
        }

        abort_if(!$attachment, 404);

        Storage::disk('public')->delete($attachment->file_path);

        $attachment->delete();
        SupportTicketUpdated::dispatch($this->ticket->fresh(), 'attachment_deleted');

        $this->refreshTicket();

        $this->dispatch('toast', message: 'Attachment deleted successfully.', type: 'success');
    }
};
?>

<div wire:key="admin-ticket-chat-{{ $ticket->id }}-{{ $refreshKey }}" x-data="{
    scrollToBottom() {
        this.$nextTick(() => {
            const el = this.$refs.chatBox;
            if (el) el.scrollTop = el.scrollHeight;
        });
    }
}" x-init="scrollToBottom()"
    x-on:ticket-replied.window="scrollToBottom()">
    <div class="mx-auto grid h-[calc(100vh-120px)] w-full max-w-7xl grid-cols-12 gap-5">

        {{-- Chat App Main --}}
        <div
            class="col-span-12 flex min-h-[80vh] lg:min-h-0 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-8">

            {{-- Chat Header --}}
            <div class="flex items-center justify-between gap-4 border-b border-slate-200 bg-white px-5 py-4">
                <div class="flex min-w-0 items-center gap-3">
                    <a href="{{ route('admin.tickets.index') }}" wire:navigate
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-slate-600 transition hover:bg-slate-100">
                        <span class="material-symbols-outlined text-xl">arrow_back</span>
                    </a>

                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-bold text-primary">
                        {{ strtoupper(substr($ticket->customer_name ?? ($ticket->user?->name ?? 'C'), 0, 1)) }}
                    </div>

                    <div class="min-w-0">
                        <h1 class="truncate text-base font-bold text-slate-900">
                            {{ $ticket->subject }}
                        </h1>

                        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                            <span class="font-mono">{{ $ticket->ticket_no }}</span>
                            <span>•</span>
                            <span>{{ $ticket->customer_name ?? ($ticket->user?->name ?? 'Customer') }}</span>
                        </div>
                    </div>
                </div>

                <div class="hidden shrink-0 items-center gap-2 sm:flex">
                    <span @class([
                        'inline-flex items-center rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-wider',
                        'bg-blue-100 text-blue-700' => $ticket->status === 'open',
                        'bg-amber-100 text-amber-700' => $ticket->status === 'pending',
                        'bg-emerald-100 text-emerald-700' => $ticket->status === 'answered',
                        'bg-slate-100 text-slate-600' => $ticket->status === 'closed',
                    ])>
                        {{ ucfirst($ticket->status) }}
                    </span>

                    <span @class([
                        'inline-flex items-center rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-wider',
                        'bg-slate-100 text-slate-600' => $ticket->priority === 'low',
                        'bg-blue-100 text-blue-700' => $ticket->priority === 'medium',
                        'bg-orange-100 text-orange-700' => $ticket->priority === 'high',
                        'bg-red-100 text-red-700' => $ticket->priority === 'urgent',
                    ])>
                        {{ ucfirst($ticket->priority) }}
                    </span>
                </div>
            </div>

            {{-- Chat Messages --}}
            <div x-ref="chatBox" class="min-h-0 flex-1 space-y-6 overflow-y-auto bg-slate-50 px-5 py-6">

                {{-- Original Customer Message --}}
                <div class="flex items-end gap-3">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-200 text-xs font-bold text-slate-700">
                        {{ strtoupper(substr($ticket->customer_name ?? ($ticket->user?->name ?? 'C'), 0, 1)) }}
                    </div>

                    <div class="max-w-[82%]">
                        <div class="mb-1 flex items-center gap-2 text-xs">
                            <span class="font-semibold text-slate-700">
                                {{ $ticket->customer_name ?? ($ticket->user?->name ?? 'Customer') }}
                            </span>

                            <span class="text-slate-400">
                                {{ $ticket->created_at?->format('M d, h:i A') }}
                            </span>
                        </div>

                        <div
                            class="rounded-2xl rounded-bl-sm border border-slate-200 bg-white px-4 py-3 text-sm leading-relaxed text-slate-700 shadow-sm">
                            {!! nl2br(e($ticket->message)) !!}
                        </div>

                        @if ($ticket->attachments->count())
                            <div class="mt-3 grid max-w-md grid-cols-2 gap-2 sm:grid-cols-3">
                                @foreach ($ticket->attachments as $attachment)
                                    <div
                                        class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                                        @if ($attachment->isImage())
                                            <a href="{{ $attachment->url() }}" target="_blank">
                                                <img src="{{ $attachment->url() }}"
                                                    class="h-28 w-full object-cover transition group-hover:scale-105"
                                                    alt="{{ $attachment->file_name }}">
                                            </a>
                                        @else
                                            <a href="{{ $attachment->url() }}" target="_blank"
                                                class="flex h-28 items-center justify-center p-3 text-center text-xs text-slate-500">
                                                {{ $attachment->file_name }}
                                            </a>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Replies --}}
                @foreach ($ticket->replies as $reply)
                    @php
                        $isAdmin = $reply->sender_type === 'admin';
                        $senderName = $reply->user?->name ?? ucfirst($reply->sender_type);
                    @endphp

                    <div wire:key="reply-{{ $reply->id }}" @class(['flex items-end gap-3', 'justify-end' => $isAdmin])>
                        @unless ($isAdmin)
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-200 text-xs font-bold text-slate-700">
                                {{ strtoupper(substr($senderName, 0, 1)) }}
                            </div>
                        @endunless

                        <div @class(['max-w-[82%]', 'text-right' => $isAdmin])>
                            <div @class([
                                'mb-1 flex items-center gap-2 text-xs',
                                'justify-end' => $isAdmin,
                            ])>
                                <span class="font-semibold text-slate-700">
                                    {{ $senderName }}
                                </span>

                                <span class="text-slate-400">
                                    {{ $reply->created_at?->format('M d, h:i A') }}
                                </span>
                            </div>

                            @if ($reply->message)
                                <div @class([
                                    'inline-block rounded-2xl px-4 py-3 text-left text-sm leading-relaxed shadow-sm',
                                    'rounded-br-sm bg-primary text-white' => $isAdmin,
                                    'rounded-bl-sm border border-slate-200 bg-white text-slate-700' => !$isAdmin,
                                ])>
                                    {!! nl2br(e($reply->message)) !!}
                                </div>
                            @endif

                            @if ($reply->attachments->count())
                                <div @class([
                                    'mt-3 grid max-w-md grid-cols-2 gap-2 sm:grid-cols-3',
                                    'ml-auto' => $isAdmin,
                                ])>
                                    @foreach ($reply->attachments as $attachment)
                                        <div
                                            class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                                            @if ($attachment->isImage())
                                                <a href="{{ $attachment->url() }}" target="_blank">
                                                    <img src="{{ $attachment->url() }}"
                                                        class="h-28 w-full object-cover transition group-hover:scale-105"
                                                        alt="{{ $attachment->file_name }}">
                                                </a>
                                            @else
                                                <a href="{{ $attachment->url() }}" target="_blank"
                                                    class="flex h-28 items-center justify-center p-3 text-center text-xs text-slate-500">
                                                    {{ $attachment->file_name }}
                                                </a>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        @if ($isAdmin)
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">
                                {{ strtoupper(substr($senderName, 0, 1)) }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Image Preview + Composer --}}
            <div class="border-t border-slate-200 bg-white p-4">
                @if ($ticket->status !== 'closed')
                    @if ($images)
                        <div class="mb-3 flex gap-2 overflow-x-auto pb-1">
                            @foreach ($images as $index => $image)
                                <div wire:key="admin-preview-image-{{ $index }}"
                                    class="relative h-20 w-20 shrink-0 overflow-hidden rounded-xl border border-slate-200 bg-slate-100">
                                    <img src="{{ $image->temporaryUrl() }}" class="h-full w-full object-cover">

                                    <button type="button" wire:click="removePreviewImage({{ $index }})"
                                        class="absolute right-1 top-1 flex h-6 w-6 items-center justify-center rounded-full bg-red-500 text-white shadow-lg transition hover:bg-red-600">
                                        <span class="material-symbols-outlined text-[14px]">close</span>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="flex items-end gap-3">
                        <label
                            class="flex h-11 w-11 shrink-0 cursor-pointer items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-slate-500 transition hover:bg-slate-100 hover:text-primary">
                            <span class="material-symbols-outlined">image</span>
                            <input type="file" wire:model="images" multiple accept="image/*" class="hidden">
                        </label>

                        <div class="min-w-0 flex-1">
                            <input wire:model="replyMessage" wire:keydown.enter.prevent="sendReply"
                                class="max-h-32 w-full resize-none rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/10"
                                placeholder="Write your reply...">

                            @error('replyMessage')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror

                            @error('images.*')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="button" wire:click="sendReply" wire:loading.attr="disabled"
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary text-white shadow-lg shadow-primary/20 transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60">
                            <span wire:loading.remove wire:target="sendReply,images" class="material-symbols-outlined">
                                send
                            </span>

                            <span wire:loading wire:target="sendReply,images"
                                class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                        </button>
                    </div>
                @else
                    <div
                        class="flex items-center justify-center gap-2 rounded-2xl bg-slate-100 px-4 py-4 text-sm font-medium text-slate-500">
                        <span class="material-symbols-outlined">lock</span>
                        This ticket is closed. Reopen it to send a reply.
                    </div>
                @endif
            </div>
        </div>

        {{-- Right Sidebar --}}
        <div class="col-span-12 min-h-0 space-y-5 xl:col-span-4">

            {{-- Customer --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="mb-5 flex items-center gap-2 text-base font-bold text-slate-900">
                    <span class="material-symbols-outlined text-primary">person</span>
                    Customer Details
                </h3>

                <div class="space-y-4 text-sm">
                    <div>
                        <p class="text-xs uppercase tracking-wider text-slate-400">Name</p>
                        <p class="font-semibold text-slate-900">
                            {{ $ticket->customer_name ?? ($ticket->user?->name ?? 'Guest Customer') }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs uppercase tracking-wider text-slate-400">Email</p>
                        <p class="break-all text-slate-600">
                            {{ $ticket->customer_email ?? ($ticket->user?->email ?? 'N/A') }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs uppercase tracking-wider text-slate-400">Phone</p>
                        <p class="text-slate-600">
                            {{ $ticket->customer_phone ?? 'N/A' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs uppercase tracking-wider text-slate-400">Department</p>
                        <p class="text-slate-600">
                            {{ $ticket->department }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Settings --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="mb-5 flex items-center gap-2 text-base font-bold text-slate-900">
                    <span class="material-symbols-outlined text-primary">tune</span>
                    Ticket Settings
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">
                            Status
                        </label>

                        <select wire:model="status"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/10">
                            <option value="open">Open</option>
                            <option value="pending">Pending</option>
                            <option value="answered">Answered</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">
                            Priority
                        </label>

                        <select wire:model="priority"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/10">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>

                    <button type="button" wire:click="saveTicketSettings" wire:loading.attr="disabled"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:opacity-90">
                        <span wire:loading.remove wire:target="saveTicketSettings">
                            Save Settings
                        </span>

                        <span wire:loading wire:target="saveTicketSettings" class="inline-flex items-center gap-2">
                            <span
                                class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                            Saving...
                        </span>
                    </button>
                </div>
            </div>

            {{-- Activity --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="mb-5 flex items-center gap-2 text-base font-bold text-slate-900">
                    <span class="material-symbols-outlined text-primary">analytics</span>
                    Ticket Activity
                </h3>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-xl bg-slate-50 p-4 text-center">
                        <p class="text-2xl font-bold text-slate-900">
                            {{ $ticket->replies->count() }}
                        </p>
                        <p class="mt-1 text-xs text-slate-500">Replies</p>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-4 text-center">
                        <p class="text-2xl font-bold text-slate-900">
                            {{ $ticket->attachments->count() + $ticket->replies->sum(fn($reply) => $reply->attachments->count()) }}
                        </p>
                        <p class="mt-1 text-xs text-slate-500">Images</p>
                    </div>
                </div>

                <div class="mt-5 space-y-3 text-sm">
                    <div class="flex justify-between gap-4 border-t border-slate-100 pt-4">
                        <span class="text-slate-500">Created</span>
                        <span class="font-semibold text-slate-900">
                            {{ $ticket->created_at?->format('M d, Y') }}
                        </span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-slate-500">Last Reply</span>
                        <span class="font-semibold text-slate-900">
                            {{ $ticket->last_reply_at ? $ticket->last_reply_at->diffForHumans() : 'No reply yet' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
