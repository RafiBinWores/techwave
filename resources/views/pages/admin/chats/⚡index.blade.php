<?php

use App\Events\AdminChatMessageSent;
use App\Enums\UserRole;
use App\Models\AdminChatMessage;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.admin-app')] #[Title('Team Chat')] class extends Component {
    use WithFileUploads;

    public ?int $selectedUserId = null;
    public string $message = '';
    public array $files = [];
    public array $employees = [];
    public ?array $activeEmployee = null;
    public array $messages = [];
    public bool $showSharedFiles = false;
    public array $sharedFiles = [];
    public ?int $previewFileId = null;

    public function mount(): void
    {
        $this->loadEmployees();

        $requestedUserId = request()->integer('user');

        if ($requestedUserId && collect($this->employees)->contains('id', $requestedUserId)) {
            $this->selectedUserId = $requestedUserId;
            $this->loadConversation();
        }
    }

    public function getListeners(): array
    {
        $authId = Auth::id();

        if (! $authId) {
            return [];
        }

        return [
            "echo-private:user.{$authId}.chat,.chat.message" => 'handleIncomingMessage',
        ];
    }

    private function teamRoles(): array
    {
        return array_map(
            fn (UserRole $role) => $role->value,
            array_filter(UserRole::cases(), fn (UserRole $role) => $role !== UserRole::CLIENT),
        );
    }

    public function loadEmployees(): void
    {
        $authId = (int) Auth::id();

        $messages = AdminChatMessage::query()
            ->where(fn ($q) => $q->where('sender_id', $authId)->orWhere('receiver_id', $authId))
            ->orderBy('created_at')
            ->get(['id', 'sender_id', 'receiver_id', 'message', 'read_at', 'created_at']);

        $lastByPartner = [];
        $unreadByPartner = [];

        foreach ($messages as $row) {
            $partnerId = $row->sender_id === $authId ? $row->receiver_id : $row->sender_id;
            $lastByPartner[$partnerId] = $row;

            if ($row->sender_id !== $authId && $row->read_at === null) {
                $unreadByPartner[$partnerId] = ($unreadByPartner[$partnerId] ?? 0) + 1;
            }
        }

        $this->employees = User::query()
            ->where('is_active', true)
            ->whereIn('role', $this->teamRoles())
            ->where('id', '!=', $authId)
            ->orderBy('name')
            ->get(['id', 'name', 'role', 'avatar'])
            ->map(function (User $user) use ($lastByPartner, $unreadByPartner) {
                /** @var AdminChatMessage|null $last */
                $last = $lastByPartner[$user->id] ?? null;

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->role instanceof UserRole ? $user->role->label() : (string) $user->role,
                    'avatar' => $user->avatar,
                    'initial' => strtoupper(mb_substr($user->name, 0, 1)),
                    'preview' => $last?->previewText(),
                    'last_at' => $last?->created_at?->toISOString(),
                    'unread' => $unreadByPartner[$user->id] ?? 0,
                ];
            })
            ->sortByDesc('last_at')
            ->values()
            ->toArray();
    }

    public function selectUser(int $userId): void
    {
        $this->selectedUserId = $userId;
        $this->showSharedFiles = false;
        $this->previewFileId = null;
        $this->resetErrorBag();
        $this->loadConversation();
        $this->dispatch('chat-thread-loaded');
    }

    public function loadConversation(): void
    {
        if (! $this->selectedUserId) {
            $this->activeEmployee = null;
            $this->messages = [];

            return;
        }

        $employee = collect($this->employees)->firstWhere('id', $this->selectedUserId);

        if ($employee === null) {
            $user = User::query()->findOrFail($this->selectedUserId);

            $employee = [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role instanceof UserRole ? $user->role->label() : (string) $user->role,
                'avatar' => $user->avatar,
                'initial' => strtoupper(mb_substr($user->name, 0, 1)),
                'preview' => null,
                'last_at' => null,
                'unread' => 0,
            ];
        }

        $this->activeEmployee = $employee;

        $this->messages = AdminChatMessage::query()
            ->with(['attachments'])
            ->between((int) Auth::id(), $this->selectedUserId)
            ->orderBy('created_at')
            ->limit(300)
            ->get()
            ->map(fn (AdminChatMessage $m) => $this->serializeMessage($m))
            ->toArray();

        $this->markAsRead();
        $this->dispatch('admin-chat-unread-changed');
        $this->loadSharedFiles();
    }

    public function toggleSharedFiles(): void
    {
        $this->showSharedFiles = ! $this->showSharedFiles;
    }

    public function closeSharedFiles(): void
    {
        $this->showSharedFiles = false;
    }

    private function loadSharedFiles(): void
    {
        if (! $this->selectedUserId) {
            $this->sharedFiles = [];

            return;
        }

        $messages = AdminChatMessage::query()
            ->with('attachments')
            ->between((int) Auth::id(), $this->selectedUserId)
            ->whereHas('attachments')
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        $files = [];

        foreach ($messages as $message) {
            foreach ($message->attachments as $attachment) {
                $files[] = [
                    'id' => $attachment->id,
                    'url' => $attachment->url(),
                    'name' => $attachment->file_name,
                    'is_image' => $attachment->isImage(),
                    'size' => $attachment->file_size,
                    'time' => $message->created_at?->format('M d, h:i A'),
                    'mine' => $message->sender_id === Auth::id(),
                ];
            }
        }

        $this->sharedFiles = $files;
    }

    public function openPreview(int $fileId): void
    {
        $this->previewFileId = $fileId;
    }

    public function closePreview(): void
    {
        $this->previewFileId = null;
    }

    private function formatSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }

    private function serializeMessage(AdminChatMessage $m): array
    {
        return [
            'id' => $m->id,
            'mine' => $m->sender_id === Auth::id(),
            'text' => $m->message,
            'time' => $m->created_at?->format('M d, h:i A'),
            'attachments' => $m->attachments->map(fn ($a) => [
                'id' => $a->id,
                'url' => $a->url(),
                'name' => $a->file_name,
                'is_image' => $a->isImage(),
                'size' => $a->file_size,
            ])->toArray(),
        ];
    }

    public function handleIncomingMessage(array $payload): void
    {
        $senderId = (int) ($payload['sender_id'] ?? 0);

        if ($senderId === (int) $this->selectedUserId) {
            $this->loadConversation();
        }

        $this->loadEmployees();
        $this->dispatch('chat-thread-loaded');
    }

    protected function rules(): array
    {
        return [
            'message' => ['nullable', 'string', 'max:5000'],
            'files' => ['nullable', 'array', 'max:5'],
            'files.*' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,csv,txt,zip'],
        ];
    }

    public function updatedFiles(): void
    {
        $this->validateOnly('files');
    }

    public function removeFile(int $index): void
    {
        unset($this->files[$index]);
        $this->files = array_values($this->files);
    }

    public function sendMessage(): void
    {
        $this->validate();

        if (! $this->selectedUserId) {
            return;
        }

        if (blank($this->message) && empty($this->files)) {
            $this->addError('message', 'Write a message or attach a file.');

            return;
        }

        $chatMessage = AdminChatMessage::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $this->selectedUserId,
            'message' => filled($this->message) ? trim($this->message) : null,
        ]);

        foreach ($this->files as $file) {
            $isOptimizable = str_starts_with((string) $file->getMimeType(), 'image/')
                && in_array(strtolower($file->getClientOriginalExtension()), ['jpg', 'jpeg', 'png', 'webp']);

            $path = $isOptimizable
                ? app(ImageService::class)->optimizeAndStore($file, 'admin-chat/'.Auth::id(), 1600)
                : $file->store('admin-chat/'.Auth::id(), 'public');

            $chatMessage->attachments()->create([
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ]);
        }

        AdminChatMessageSent::dispatch($chatMessage->fresh(['attachments']));

        $this->reset('message', 'files');

        $this->loadEmployees();
        $this->loadConversation();
        $this->dispatch('chat-thread-loaded');
    }

    public function markAsRead(): void
    {
        if (! $this->selectedUserId) {
            return;
        }

        AdminChatMessage::query()
            ->where('sender_id', $this->selectedUserId)
            ->where('receiver_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
};
?>

<div x-data="{
        listVisible: ! new URLSearchParams(window.location.search).has('user'),
        openConversation() {
            if (! this.listVisible) return;

            this.listVisible = false;
            history.pushState({ chatListClosed: true }, '');
        },
        closeConversation() {
            this.listVisible = true;

            if (history.state?.chatListClosed) history.back();
        },
        scrollToBottom() {
            this.$nextTick(() => {
                const el = this.$refs.chatBox;
                if (el) el.scrollTop = el.scrollHeight;
            });
        }
    }" x-init="scrollToBottom();
        window.addEventListener('popstate', (e) => { this.listVisible = ! (e.state && e.state.chatListClosed); })"
    x-on:chat-thread-loaded.window="scrollToBottom()">
    <div class="mx-auto grid h-[calc(100vh-120px)] w-full max-w-7xl grid-cols-12 gap-5">

        {{-- Employees Sidebar --}}
        <div x-bind:class="listVisible ? 'flex' : 'hidden'"
            class="col-span-12 min-h-0 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm md:flex md:col-span-5 xl:col-span-3">
            <div class="border-b border-slate-200 px-5 py-4">
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <h2 class="font-manrope text-base font-bold text-slate-900">Team Chat</h2>
                        <p class="mt-0.5 text-xs text-slate-500">{{ count($employees) }} teammates</p>
                    </div>

                    <button type="button" wire:click="loadEmployees"
                        class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-slate-600 transition hover:bg-slate-100">
                        <span class="material-symbols-outlined text-lg">refresh</span>
                    </button>
                </div>
            </div>

            <div class="min-h-0 flex-1 divide-y divide-slate-100 overflow-y-auto">
                @forelse ($employees as $employee)
                    <button type="button" wire:click="selectUser({{ $employee['id'] }})"
                        x-on:click="openConversation()"
                        class="flex w-full cursor-pointer items-center gap-3 px-4 py-3 text-left transition {{ $selectedUserId === $employee['id'] ? 'bg-blue-50/70' : 'hover:bg-slate-50' }}">
                        <span class="relative shrink-0">
                            @if ($employee['avatar'])
                                <img src="{{ asset('storage/'.$employee['avatar']) }}" alt="{{ $employee['name'] }}"
                                    class="h-11 w-11 rounded-full object-cover">
                            @else
                                <span
                                    class="flex h-11 w-11 items-center justify-center rounded-full bg-primary/10 text-sm font-bold text-primary">
                                    {{ $employee['initial'] }}
                                </span>
                            @endif

                            @if ($employee['unread'] > 0)
                                <span
                                    class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">
                                    {{ $employee['unread'] > 99 ? '99+' : $employee['unread'] }}
                                </span>
                            @endif
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="flex items-center justify-between gap-2">
                                <span class="truncate text-sm font-semibold text-slate-900">
                                    {{ $employee['name'] }}
                                </span>
                                @if ($employee['last_at'])
                                    <span class="shrink-0 text-[10px] text-slate-400">
                                        {{ \Illuminate\Support\Carbon::parse($employee['last_at'])->diffForHumans(short: true) }}
                                    </span>
                                @endif
                            </span>

                            <span class="mt-0.5 flex items-center justify-between gap-2">
                                <span class="truncate text-xs {{ $employee['unread'] > 0 ? 'font-semibold text-slate-700' : 'text-slate-400' }}">
                                    {{ $employee['preview'] ?? 'No messages yet' }}
                                </span>
                            </span>
                        </span>
                    </button>
                @empty
                    <div class="p-8 text-center text-sm text-slate-500">
                        No teammates found yet.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Conversation --}}
        <div x-bind:class="! listVisible ? 'flex' : 'hidden'"
            class="relative col-span-12 min-h-[60vh] flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm md:flex md:col-span-7 xl:col-span-9">

            @if ($activeEmployee)
                {{-- Thread Header --}}
                <div class="flex items-center gap-3 border-b border-slate-200 px-5 py-4">
                    <button type="button" x-on:click="closeConversation()"
                        class="-ml-1 flex h-9 w-9 shrink-0 cursor-pointer items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 md:hidden"
                        aria-label="Back to chat list">
                        <span class="material-symbols-outlined">arrow_back</span>
                    </button>

                    @if ($activeEmployee['avatar'])
                        <img src="{{ asset('storage/'.$activeEmployee['avatar']) }}" alt="{{ $activeEmployee['name'] }}"
                            class="h-11 w-11 shrink-0 rounded-full object-cover">
                    @else
                        <span
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-bold text-primary">
                            {{ $activeEmployee['initial'] }}
                        </span>
                    @endif

                    <div class="min-w-0">
                        <h1 class="truncate font-manrope text-base font-bold text-slate-900">
                            {{ $activeEmployee['name'] }}
                        </h1>
                        <p class="mt-0.5 text-xs text-slate-500">{{ $activeEmployee['role'] }}</p>
                    </div>

                    <button type="button" wire:click="toggleSharedFiles"
                        class="ml-auto flex shrink-0 cursor-pointer items-center gap-1.5 rounded-full border px-3 py-2 text-xs font-semibold transition {{ $showSharedFiles ? 'border-primary/30 bg-primary/10 text-primary' : 'border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100' }}">
                        <span class="material-symbols-outlined text-base">folder_open</span>
                        <span class="hidden sm:inline">Media</span>
                    </button>
                </div>

                {{-- Messages --}}
                <div x-ref="chatBox" class="min-h-0 flex-1 space-y-5 overflow-y-auto bg-slate-50 px-5 py-6">
                    @forelse ($messages as $msg)
                        <div class="flex items-end gap-3 {{ $msg['mine'] ? 'justify-end' : '' }}">
                            @if (! $msg['mine'])
                                <span
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-200 text-xs font-bold text-slate-700">
                                    {{ $activeEmployee['initial'] }}
                                </span>
                            @endif

                            <div class="max-w-[82%]">
                                <div class="mb-1 flex items-center gap-2 text-xs {{ $msg['mine'] ? 'justify-end' : '' }}">
                                    @if ($msg['mine'])
                                        <span class="text-slate-400">You</span>
                                    @endif

                                    <span class="text-slate-400">{{ $msg['time'] }}</span>
                                </div>

                                @if ($msg['text'])
                                    <div class="rounded-2xl px-4 py-3 text-sm leading-relaxed shadow-sm {{ $msg['mine'] ? 'rounded-br-sm bg-primary text-white' : 'rounded-bl-sm border border-slate-200 bg-white text-slate-700' }}">
                                        {!! nl2br(e($msg['text'])) !!}
                                    </div>
                                @endif

                                @if (count($msg['attachments']))
                                    <div class="mt-2 flex max-w-md flex-wrap gap-2 {{ $msg['mine'] ? 'justify-end' : 'justify-start' }}">
                                        @foreach ($msg['attachments'] as $attachment)
                                            <div class="group relative w-36 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                                                @if ($attachment['is_image'])
                                                    <button type="button" wire:click="openPreview({{ $attachment['id'] }})"
                                                        class="block h-28 w-full cursor-pointer overflow-hidden">
                                                        <img src="{{ $attachment['url'] }}"
                                                            class="h-28 w-full object-cover transition group-hover:scale-105"
                                                            alt="{{ $attachment['name'] }}">
                                                    </button>
                                                @else
                                                    <button type="button" wire:click="openPreview({{ $attachment['id'] }})"
                                                        class="flex h-28 w-full cursor-pointer flex-col items-center justify-center gap-1 p-2 text-center transition hover:bg-slate-50">
                                                        <span class="material-symbols-outlined text-3xl text-slate-400">description</span>
                                                        <span class="line-clamp-2 break-all text-[11px] font-medium text-slate-600">
                                                            {{ $attachment['name'] }}
                                                        </span>
                                                        <span class="text-[10px] font-semibold uppercase tracking-wide text-primary">View</span>
                                                    </button>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="flex h-full items-center justify-center">
                            <div class="text-center">
                                <span class="material-symbols-outlined mx-auto mb-2 block text-4xl text-slate-300">forum</span>
                                <p class="text-sm font-medium text-slate-500">No messages yet.</p>
                                <p class="mt-1 text-xs text-slate-400">Say hi to {{ $activeEmployee['name'] }}!</p>
                            </div>
                        </div>
                    @endforelse
                </div>

                {{-- Shared Files Panel --}}
                @if ($showSharedFiles && $activeEmployee)
                    <div class="absolute inset-0 z-20 flex flex-col bg-white">
                        <div class="flex items-center gap-3 border-b border-slate-200 px-5 py-4">
                            <span
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                                <span class="material-symbols-outlined text-lg">folder_open</span>
                            </span>

                            <div class="min-w-0">
                                <h2 class="font-manrope text-base font-bold text-slate-900">Media</h2>
                                <p class="mt-0.5 truncate text-xs text-slate-500">
                                    With {{ $activeEmployee['name'] }}
                                </p>
                            </div>

                            <button type="button" wire:click="closeSharedFiles"
                                class="ml-auto flex h-9 w-9 shrink-0 cursor-pointer items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>

                        <div class="min-h-0 flex-1 overflow-y-auto bg-slate-50 px-5 py-5">
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
                                @forelse ($sharedFiles as $file)
                                    @if ($file['is_image'])
                                        <button type="button" wire:click="openPreview({{ $file['id'] }})"
                                            class="group cursor-pointer overflow-hidden rounded-xl border border-slate-200 bg-white text-left shadow-sm transition hover:border-primary/40 hover:shadow-md">
                                            <img src="{{ $file['url'] }}" alt="{{ $file['name'] }}"
                                                class="h-28 w-full object-cover transition group-hover:scale-105">
                                            <span class="block px-3 py-2">
                                                <span class="block truncate text-xs font-semibold text-slate-700">
                                                    {{ $file['name'] }}
                                                </span>
                                                <span class="mt-0.5 block text-[10px] text-slate-400">
                                                    {{ $this->formatSize($file['size']) }} · {{ $file['time'] }}
                                                </span>
                                            </span>
                                        </button>
                                    @else
                                        <button type="button" wire:click="openPreview({{ $file['id'] }})"
                                            class="group cursor-pointer overflow-hidden rounded-xl border border-slate-200 bg-white text-left shadow-sm transition hover:border-primary/40 hover:shadow-md">
                                            <span class="flex h-28 items-center justify-center bg-slate-50 transition group-hover:bg-slate-100">
                                                <span class="material-symbols-outlined text-4xl text-slate-300">description</span>
                                            </span>
                                            <span class="block px-3 py-2">
                                                <span class="block truncate text-xs font-semibold text-slate-700">
                                                    {{ $file['name'] }}
                                                </span>
                                                <span class="mt-0.5 block text-[10px] text-slate-400">
                                                    {{ $this->formatSize($file['size']) }} · {{ $file['time'] }}
                                                </span>
                                            </span>
                                        </button>
                                    @endif
                                @empty
                                    <div class="col-span-full flex min-h-64 items-center justify-center">
                                        <div class="text-center">
                                            <span class="material-symbols-outlined mx-auto mb-2 block text-4xl text-slate-300">folder_off</span>
                                            <p class="text-sm font-medium text-slate-500">No media shared yet.</p>
                                            <p class="mt-1 text-xs text-slate-400">Attachments you exchange will appear here.</p>
                                        </div>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Media Preview Modal --}}
                @php
                    $previewFile = $previewFileId !== null ? collect($sharedFiles)->firstWhere('id', $previewFileId) : null;
                @endphp

                @if ($previewFile)
                    <div class="absolute inset-0 z-30 flex flex-col bg-slate-900/80 p-4 backdrop-blur-sm">
                        <div class="flex items-center gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-white">{{ $previewFile['name'] }}</p>
                                <p class="mt-0.5 text-xs text-slate-300">
                                    {{ $this->formatSize($previewFile['size']) }} · {{ $previewFile['time'] }} ·
                                    {{ $previewFile['mine'] ? 'You' : $activeEmployee['initial'] }}
                                </p>
                            </div>

                            <a href="{{ $previewFile['url'] }}" target="_blank" rel="noopener"
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20">
                                <span class="material-symbols-outlined text-lg">download</span>
                            </a>

                            <button type="button" wire:click="closePreview"
                                class="flex h-9 w-9 shrink-0 cursor-pointer items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>

                        <div class="mt-3 min-h-0 flex-1 overflow-hidden rounded-xl bg-white/5">
                            @if ($previewFile['is_image'])
                                <img src="{{ $previewFile['url'] }}" alt="{{ $previewFile['name'] }}"
                                    class="mx-auto max-h-full w-auto max-w-full object-contain">
                            @else
                                <iframe src="{{ $previewFile['url'] }}" title="{{ $previewFile['name'] }}"
                                    class="h-full w-full border-0 bg-white"></iframe>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Pending Attachments Preview --}}
                @if (count($files))
                    <div class="flex flex-wrap gap-2 border-t border-slate-200 px-5 pt-3">
                        @foreach ($files as $index => $file)
                            <span class="relative inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 py-1.5 pl-2 pr-8 text-xs text-slate-600">
                                @if (str_starts_with((string) $file->getMimeType(), 'image/'))
                                    <img src="{{ $file->temporaryUrl() }}" class="h-8 w-8 rounded-lg object-cover" alt="">
                                @else
                                    <span class="material-symbols-outlined text-lg text-slate-400">description</span>
                                @endif

                                <span class="max-w-32 truncate">{{ $file->getClientOriginalName() }}</span>

                                <button type="button" wire:click="removeFile({{ $index }})"
                                    class="absolute right-1 top-1/2 -translate-y-1/2 cursor-pointer text-slate-400 transition hover:text-red-500">
                                    <span class="material-symbols-outlined text-sm">close</span>
                                </button>
                            </span>
                        @endforeach
                    </div>
                @endif

                {{-- Composer --}}
                <form wire:submit.prevent="sendMessage" class="border-t border-slate-200 px-4 py-3">
                    @error('message')
                        <p class="mb-2 px-1 text-xs font-medium text-red-500">{{ $message }}</p>
                    @enderror

                    <div class="flex items-end gap-2">
                        <label
                            class="flex h-11 w-11 shrink-0 cursor-pointer items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700">
                            <span class="material-symbols-outlined">attach_file</span>

                            <input type="file" wire:model="files" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.zip,image/*" class="hidden">

                            <span wire:loading wire:target="files" class="hidden"></span>
                        </label>

                        <textarea wire:model="message" rows="1" placeholder="Type a message..."
                            class="no-scrollbar max-h-32 min-h-11 flex-1 resize-none rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 focus:border-primary/40 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/15"
                            x-on:input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 128) + 'px'"></textarea>

                        <button type="submit"
                            class="flex h-11 w-11 shrink-0 cursor-pointer items-center justify-center rounded-full bg-primary text-white shadow-sm transition hover:opacity-90 disabled:opacity-60"
                            wire:loading.attr="disabled" wire:target="sendMessage">
                            <span class="material-symbols-outlined" wire:loading.remove wire:target="sendMessage">send</span>
                            <span class="material-symbols-outlined animate-spin hidden" wire:loading wire:target="sendMessage">progress_activity</span>
                        </button>
                    </div>

                    @error('files.*')
                        <p class="mt-2 px-1 text-xs font-medium text-red-500">{{ $message }}</p>
                    @enderror
                </form>
            @else
                <div class="flex flex-1 items-center justify-center">
                    <div class="text-center">
                        <span class="material-symbols-outlined mx-auto mb-3 block text-5xl text-slate-300">forum</span>
                        <p class="font-manrope text-sm font-bold text-slate-600">Select a teammate to start chatting</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
