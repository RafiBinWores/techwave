<?php

use App\Events\SupportTicketUpdated;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public SupportTicket $ticket;

    public string $replyMessage = '';
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
        abort_if($ticket->user_id !== Auth::id(), 403);

        if (is_null($ticket->client_read_at)) {
            $ticket->update([
                'client_read_at' => now(),
            ]);
        }

        // Load fresh with all relations after update
        $this->ticket = $ticket->fresh(['user', 'attachments', 'replies.user', 'replies.attachments']);
    }

    public function refreshTicket(): void
    {
        $this->ticket = $this->ticket->fresh(['user', 'attachments', 'replies.user', 'replies.attachments']);
    }

    protected function rules(): array
    {
        return [
            'replyMessage' => ['nullable', 'string', 'max:5000'],
            'images.*' => ['nullable', 'image', 'max:4096'],
        ];
    }

    public function removePreviewImage(int $index): void
    {
        if (isset($this->images[$index])) {
            unset($this->images[$index]);
            $this->images = array_values($this->images);
        }
    }

    public function sendReply(): void
    {
        $this->validate();

        abort_if($this->ticket->status === 'closed', 403);

        if (blank($this->replyMessage) && empty($this->images)) {
            $this->addError('replyMessage', 'Please write a reply or attach an image.');
            return;
        }

        $reply = $this->ticket->replies()->create([
            'user_id' => Auth::id(),
            'sender_type' => 'customer',
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
            'status' => 'open',
            'last_reply_at' => now(),
            'client_read_at' => now(),
            'admin_read_at' => null,
        ]);

        SupportTicketUpdated::dispatch($this->ticket->fresh(), 'client_replied');

        $this->replyMessage = '';
        $this->images = [];

        $this->refreshTicket();

        $this->dispatch('ticket-replied');
    }
};
?>

<div wire:key="client-ticket-chat-{{ $ticket->id }}-{{ $refreshKey }}" x-data="{
    imagePreviewOpen: false,
    imagePreviewSrc: '',
    imagePreviewName: '',

    openImagePreview(src, name = '') {
        this.imagePreviewSrc = src;
        this.imagePreviewName = name;
        this.imagePreviewOpen = true;
        document.body.style.overflow = 'hidden';
    },

    closeImagePreview() {
        this.imagePreviewOpen = false;
        this.imagePreviewSrc = '';
        this.imagePreviewName = '';
        document.body.style.overflow = '';
    },

    scrollToBottom() {
        this.$nextTick(() => {
            const el = this.$refs.chatBox;
            if (el) el.scrollTop = el.scrollHeight;
        });
    }
}" x-init="scrollToBottom()"
    x-on:ticket-replied.window="scrollToBottom()" x-on:keydown.escape.window="closeImagePreview()">
    <div
        class="mx-auto grid min-h-[calc(100vh-120px)] w-full max-w-350 grid-cols-12 gap-4 px-3 sm:gap-6 sm:px-6 lg:py-4 lg:px-8">

        {{-- Chat Main --}}
        <div
            class="col-span-12 flex h-[calc(100vh-120px)] min-h-0 flex-col overflow-hidden border border-white/10 bg-white/6 backdrop-blur-xl sm:h-[calc(100vh-150px)] sm:rounded-[28px] xl:col-span-8 rounded-2xl">

            {{-- Header --}}
            <div class="flex items-start justify-between gap-3 border-b border-white/10 px-3 py-3 sm:px-5 sm:py-4">
                <div class="min-w-0">
                    <a href="{{ route('client.tickets.index') }}" wire:navigate
                        class="mb-2 inline-flex items-center gap-1 text-xs font-semibold text-blue-100/55 transition hover:text-white">
                        <span class="material-symbols-outlined text-base">arrow_back</span>
                        Back to Tickets
                    </a>

                    <h1 class="truncate text-base font-bold text-white sm:text-lg">
                        {{ $ticket->subject }}
                    </h1>

                    <p class="mt-1 font-mono text-xs text-blue-100/45">
                        {{ $ticket->ticket_no }}
                    </p>
                </div>

                <span @class([
                    'inline-flex shrink-0 rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-wider',
                    'bg-blue-400/15 text-blue-200' => $ticket->status === 'open',
                    'bg-amber-400/15 text-amber-200' => $ticket->status === 'pending',
                    'bg-emerald-400/15 text-emerald-200' => $ticket->status === 'answered',
                    'bg-slate-400/15 text-slate-200' => $ticket->status === 'closed',
                ])>
                    {{ ucfirst($ticket->status) }}
                </span>
            </div>

            {{-- Messages --}}
            <div x-ref="chatBox"
                class="chat-scrollbar min-h-0 flex-1 space-y-5 overflow-y-auto px-3 py-4 sm:px-5 sm:py-6 [&::-webkit-scrollbar]:w-2 [&::-webkit-scrollbar-thumb]:bg-white/10 [&::-webkit-scrollbar-thumb]:rounded-full">

                {{-- Original Ticket Message - User Side --}}
                <div class="flex items-end justify-end gap-2 sm:gap-3">
                    <div class="max-w-[88%] text-right sm:max-w-[82%]">
                        <div class="mb-1 flex items-center justify-end gap-2 text-xs">
                            <span class="text-blue-100/45">
                                {{ $ticket->created_at?->format('M d, h:i A') }}
                            </span>

                            <span class="font-semibold text-blue-100/75">
                                You
                            </span>
                        </div>

                        <div
                            class="inline-block rounded-2xl rounded-br-sm bg-linear-to-r from-blue-500 to-sky-400 px-4 py-3 text-left text-sm leading-relaxed text-white shadow-lg shadow-blue-500/15">
                            {!! nl2br(e($ticket->message)) !!}
                        </div>

                        @if ($ticket->attachments->count())
                            <div class="ml-auto mt-3 grid max-w-xs grid-cols-2 gap-2 sm:max-w-sm sm:grid-cols-3">
                                @foreach ($ticket->attachments as $attachment)
                                    @if ($attachment->isImage())
                                        <button type="button"
                                            @click="openImagePreview('{{ $attachment->url() }}', '{{ addslashes($attachment->file_name) }}')"
                                            class="group overflow-hidden rounded-xl border border-white/10 bg-white/8">
                                            <img src="{{ $attachment->url() }}"
                                                class="h-24 w-full object-cover transition group-hover:scale-105 sm:h-28"
                                                alt="{{ $attachment->file_name }}">
                                        </button>
                                    @else
                                        <a href="{{ $attachment->url() }}" target="_blank"
                                            class="flex h-24 items-center justify-center rounded-xl border border-white/10 bg-white/8 p-3 text-center text-xs text-blue-100/60 sm:h-28">
                                            {{ $attachment->file_name }}
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-linear-to-r from-blue-500 to-sky-400 text-xs font-bold text-white sm:h-9 sm:w-9">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </div>
                </div>

                {{-- Replies --}}
                @foreach ($ticket->replies as $reply)
                    @php
                        $isCustomer = $reply->sender_type === 'customer';
                        $senderName = $isCustomer ? 'You' : $reply->user?->name ?? 'Support';
                        $senderInitial = strtoupper(
                            substr($senderName === 'You' ? auth()->user()->name ?? 'U' : $senderName, 0, 1),
                        );
                    @endphp

                    <div wire:key="reply-{{ $reply->id }}" @class([
                        'flex items-end gap-2 sm:gap-3',
                        'justify-end' => $isCustomer,
                    ])>

                        @unless ($isCustomer)
                            <div
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/10 text-xs font-bold text-white sm:h-9 sm:w-9">
                                {{ $senderInitial }}
                            </div>
                        @endunless

                        <div @class(['max-w-[88%] sm:max-w-[82%]', 'text-right' => $isCustomer])>
                            <div @class([
                                'mb-1 flex items-center gap-2 text-xs',
                                'justify-end' => $isCustomer,
                            ])>
                                @if ($isCustomer)
                                    <span class="text-blue-100/45">
                                        {{ $reply->created_at?->format('M d, h:i A') }}
                                    </span>

                                    <span class="font-semibold text-blue-100/75">
                                        You
                                    </span>
                                @else
                                    <span class="font-semibold text-blue-100/75">
                                        {{ $senderName }}
                                    </span>

                                    <span class="text-blue-100/45">
                                        {{ $reply->created_at?->format('M d, h:i A') }}
                                    </span>
                                @endif
                            </div>

                            @if ($reply->message)
                                <div @class([
                                    'inline-block rounded-2xl px-4 py-3 text-left text-sm leading-relaxed',
                                    'rounded-br-sm bg-linear-to-r from-blue-500 to-sky-400 text-white shadow-lg shadow-blue-500/15' => $isCustomer,
                                    'rounded-bl-sm border border-white/10 bg-white/8 text-blue-50' => !$isCustomer,
                                ])>
                                    {!! nl2br(e($reply->message)) !!}
                                </div>
                            @endif

                            @if ($reply->attachments->count())
                                <div @class([
                                    'mt-3 grid max-w-xs grid-cols-2 gap-2 sm:max-w-sm sm:grid-cols-3',
                                    'ml-auto' => $isCustomer,
                                ])>
                                    @foreach ($reply->attachments as $attachment)
                                        @if ($attachment->isImage())
                                            <button type="button"
                                                @click="openImagePreview('{{ $attachment->url() }}', '{{ addslashes($attachment->file_name) }}')"
                                                class="group overflow-hidden rounded-xl border border-white/10 bg-white/8">
                                                <img src="{{ $attachment->url() }}"
                                                    class="h-24 w-full object-cover transition group-hover:scale-105 sm:h-28"
                                                    alt="{{ $attachment->file_name }}">
                                            </button>
                                        @else
                                            <a href="{{ $attachment->url() }}" target="_blank"
                                                class="flex h-24 items-center justify-center rounded-xl border border-white/10 bg-white/8 p-3 text-center text-xs text-blue-100/60 sm:h-28">
                                                {{ $attachment->file_name }}
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        @if ($isCustomer)
                            <div
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-linear-to-r from-blue-500 to-sky-400 text-xs font-bold text-white sm:h-9 sm:w-9">
                                {{ $senderInitial }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Composer --}}
            <div class="border-t border-white/10 p-3 sm:p-4">
                @if ($ticket->status !== 'closed')
                    @if ($images)
                        <div class="mb-3 flex gap-2 overflow-x-auto pb-1">
                            @foreach ($images as $index => $image)
                                <div wire:key="reply-preview-image-{{ $index }}"
                                    class="relative h-20 w-20 shrink-0 overflow-hidden rounded-xl border border-white/10 bg-white/8">

                                    <img src="{{ $image->temporaryUrl() }}" class="h-full w-full object-cover">

                                    <button type="button" wire:click="removePreviewImage({{ $index }})"
                                        class="absolute right-1 top-1 flex h-6 w-6 items-center justify-center rounded-full bg-red-500 text-white shadow-lg transition hover:bg-red-600">
                                        <span class="material-symbols-outlined text-[14px]">close</span>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="flex items-end gap-2 sm:gap-3">
                        <label
                            class="flex h-10 w-10 shrink-0 cursor-pointer items-center justify-center rounded-full border border-white/10 bg-white/8 text-blue-100/70 transition hover:bg-white/12 sm:h-11 sm:w-11">
                            <span class="material-symbols-outlined">image</span>
                            <input type="file" wire:model="images" multiple accept="image/*" class="hidden">
                        </label>

                        <input wire:model="replyMessage" wire:keydown.enter.prevent="sendReply"
                            class="min-w-0 flex-1 rounded-2xl border border-white/10 bg-white/8 px-3 py-2.5 text-sm text-white placeholder:text-blue-100/35 outline-none focus:border-blue-300/40 focus:ring-2 focus:ring-blue-400/10 sm:px-4 sm:py-3"
                            placeholder="Write your reply...">

                        <button type="button" wire:click="sendReply" wire:loading.attr="disabled"
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-linear-to-r from-blue-500 to-sky-400 text-white shadow-lg shadow-blue-500/20 transition hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-60 sm:h-11 sm:w-11">
                            <span wire:loading.remove wire:target="sendReply,images" class="material-symbols-outlined">
                                send
                            </span>

                            <span wire:loading wire:target="sendReply,images"
                                class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                        </button>
                    </div>

                    @error('replyMessage')
                        <p class="mt-2 text-xs text-red-300">{{ $message }}</p>
                    @enderror

                    @error('images.*')
                        <p class="mt-2 text-xs text-red-300">{{ $message }}</p>
                    @enderror
                @else
                    <div class="rounded-2xl bg-white/8 px-4 py-4 text-center text-sm text-blue-100/60">
                        This ticket is closed.
                    </div>
                @endif
            </div>
        </div>

        {{-- Right Sidebar --}}
        <div class="col-span-12 space-y-4 xl:col-span-4">
            <div class="client-card p-6">
                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                    Ticket Details
                </p>

                <h2 class="mt-2 text-2xl font-bold text-white">
                    Summary
                </h2>

                <div class="mt-6 space-y-4 text-sm">
                    <div class="flex justify-between gap-4 border-b border-white/10 pb-3">
                        <span class="text-blue-100/55">Department</span>
                        <span class="font-semibold text-white">{{ $ticket->department }}</span>
                    </div>

                    <div class="flex justify-between gap-4 border-b border-white/10 pb-3">
                        <span class="text-blue-100/55">Priority</span>
                        <span class="font-semibold capitalize text-white">{{ $ticket->priority }}</span>
                    </div>

                    <div class="flex justify-between gap-4 border-b border-white/10 pb-3">
                        <span class="text-blue-100/55">Replies</span>
                        <span class="font-semibold text-white">{{ $ticket->replies->count() }}</span>
                    </div>

                    <div class="flex justify-between gap-4 border-b border-white/10 pb-3">
                        <span class="text-blue-100/55">Images</span>
                        <span class="font-semibold text-white">
                            {{ $ticket->attachments->count() + $ticket->replies->sum(fn($reply) => $reply->attachments->count()) }}
                        </span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-blue-100/55">Created</span>
                        <span class="font-semibold text-white">{{ $ticket->created_at?->format('M d, Y') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Image Preview Modal --}}
    <div x-cloak x-show="imagePreviewOpen" x-transition.opacity
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/85 px-4 py-6 backdrop-blur-sm"
        @click.self="closeImagePreview()">

        <div x-show="imagePreviewOpen" x-transition.scale.origin.center
            class="relative w-full max-w-5xl overflow-hidden rounded-3xl border border-white/10 bg-white shadow-2xl">

            <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-4 py-3">
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-slate-900"
                        x-text="imagePreviewName || 'Image Preview'"></p>
                    <p class="text-xs text-slate-500">Click outside or press ESC to close</p>
                </div>

                <button type="button" @click="closeImagePreview()"
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-600 transition hover:bg-red-100 hover:text-red-600">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <div class="flex max-h-[78vh] items-center justify-center bg-slate-950 p-3">
                <img :src="imagePreviewSrc" :alt="imagePreviewName"
                    class="max-h-[74vh] max-w-full rounded-2xl object-contain">
            </div>
        </div>
    </div>
</div>
