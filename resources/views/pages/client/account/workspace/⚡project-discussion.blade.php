<?php

use App\Models\WorkspaceProject;
use App\Models\WorkspaceProjectMessage;
use App\Models\WorkspaceProjectMessageAttachment;
use App\Services\ImageService;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Project Discussion')] class extends Component {
    use WithFileUploads;

    public WorkspaceProject $project;
    public string $message = '';
    public array $files = [];
    public array $messages = [];

    public function mount(WorkspaceProject $project): void
    {
        $user = auth()->user();

        if ($user?->role !== 'admin' && (int) $project->client_id !== (int) $user?->id) {
            abort(403);
        }

        $this->project = WorkspaceProject::query()
            ->with(['creator', 'members', 'client'])
            ->where('is_active', true)
            ->findOrFail($project->getKey());

        $this->loadConversation();
    }

    public function getListeners(): array
    {
        $authId = auth()->id();

        if (! $authId) {
            return [];
        }

        return [
            "echo-private:project.{$this->project->id}.messages,.project.message.sent" => 'handleIncomingMessage',
        ];
    }

    public function loadConversation(): void
    {
        $this->messages = WorkspaceProjectMessage::query()
            ->with(['sender', 'attachments'])
            ->where('workspace_project_id', $this->project->getKey())
            ->orderBy('created_at')
            ->limit(300)
            ->get()
            ->map(fn (WorkspaceProjectMessage $m) => $this->serializeMessage($m))
            ->toArray();

        WorkspaceProjectMessage::markProjectAsRead($this->project);
        $this->dispatch('discussion-thread-loaded');
    }

    private function serializeMessage(WorkspaceProjectMessage $m): array
    {
        return [
            'id' => $m->id,
            'mine' => $m->sender_id === auth()->id(),
            'sender_name' => $m->sender?->name ?? 'Former team member',
            'sender_initial' => strtoupper(mb_substr($m->sender?->name ?? '?', 0, 1)),
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

        if ($senderId !== (int) auth()->id()) {
            $this->loadConversation();
        }
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

        if (blank($this->message) && empty($this->files)) {
            $this->addError('message', 'Write a message or attach a file.');

            return;
        }

        $attachments = [];

        foreach ($this->files as $file) {
            $isOptimizable = str_starts_with((string) $file->getMimeType(), 'image/')
                && in_array(strtolower($file->getClientOriginalExtension()), ['jpg', 'jpeg', 'png', 'webp']);

            $path = $isOptimizable
                ? app(ImageService::class)->optimizeAndStore($file, 'workspace/project-chat/'.auth()->id(), 1600)
                : $file->store('workspace/project-chat/'.auth()->id(), 'public');

            $attachments[] = [
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ];
        }

        WorkspaceProjectMessage::send($this->project, $this->message, $attachments);

        $this->reset('message', 'files');
        $this->loadConversation();
        $this->dispatch('discussion-thread-loaded');
    }

    public function activityThreads(): array
    {
        return $this->project->updates()
            ->where('parent_id', null)
            ->latest()
            ->get()
            ->map(fn ($update) => ['update' => $update, 'replies' => []])
            ->all();
    }

    public function healthBadge(string $health): string
    {
        return match ($health) {
            'at_risk' => 'text-amber-700 ring-amber-200',
            'off_track' => 'text-red-700 ring-red-200',
            default => 'text-emerald-700 ring-emerald-200',
        };
    }
};
?>

<div x-data="{ sidebarOpen: false, lightbox: false, lightboxSrc: '', lightboxName: '' }" class="relative min-h-screen text-white">

    <div class="mx-auto max-w-350 px-4 py-6 sm:px-6 lg:px-8">
        <div
            class="rounded-2xl border border-white/10 bg-white/6 shadow-[0_20px_80px_rgba(0,0,0,0.22)] backdrop-blur-2xl">
            <div class="flex min-h-[calc(100vh-3rem)]">

                {{-- Mobile Overlay --}}
                <div x-show="sidebarOpen" x-transition.opacity
                    class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm lg:hidden" @click="sidebarOpen = false"
                    style="display:none;">
                </div>

                {{-- Sidebar --}}
                <livewire:shared.user-sidebar />

                {{-- Main --}}
                <div class="min-w-0 flex-1 p-4 sm:p-6 lg:p-8">

                    {{-- Header --}}
                    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-center gap-3">
                            <a
                                href="{{ route('account.workspace-project.activity', $project) }}"
                                wire:navigate
                                class="flex h-11 w-11 items-center justify-center rounded-2xl border border-white/10 bg-white/8 text-white shadow-[0_10px_30px_rgba(0,0,0,0.18)] backdrop-blur-xl transition hover:bg-white/12">
                                <span class="material-symbols-outlined text-xl">arrow_back</span>
                            </a>

                            <div
                                class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-white/10 shadow-inner"
                                style="background: {{ $project->icon_color ?: '#4f46e5' }}22;">
                                <span class="material-symbols-outlined text-xl" style="color: {{ $project->icon_color ?: '#4f46e5' }}">
                                    {{ $project->icon ?: 'space_dashboard' }}
                                </span>
                            </div>

                            <div>
                                <h1 class="text-2xl font-bold text-white sm:text-3xl">
                                    Discussion
                                </h1>

                                <p class="mt-1 text-sm text-blue-100/50">
                                    {{ $project->name }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <a href="{{ route('account.workspace-project.activity', $project) }}"
                                wire:navigate
                                class="rounded-full border border-white/10 bg-white/8 px-3 py-1 text-xs font-medium text-blue-100/70 transition hover:bg-white/12">
                                Activity
                            </a>
                        </div>
                    </div>

                    {{-- Chat Container --}}
                    <div class="flex h-[calc(100vh-200px)] flex-col overflow-hidden rounded-[28px] border border-white/10 bg-white/8 shadow-[0_16px_50px_rgba(0,0,0,0.18)] backdrop-blur-2xl">

                        {{-- Messages --}}
                        <div x-ref="discussionBox" x-init="$nextTick(() => { $refs.discussionBox.scrollTop = $refs.discussionBox.scrollHeight })"
                            x-on:discussion-thread-loaded.window="$nextTick(() => { $refs.discussionBox.scrollTop = $refs.discussionBox.scrollHeight })"
                            class="discussion-scroll min-h-0 flex-1 space-y-4 overflow-y-auto p-5">

                            @forelse ($messages as $msg)
                                <div class="flex items-end gap-3 {{ $msg['mine'] ? 'justify-end' : '' }}">
                                    @if (! $msg['mine'])
                                        <span
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-white/15 bg-white/10 text-xs font-bold text-cyan-200">
                                            {{ $msg['sender_initial'] }}
                                        </span>
                                    @endif

                                    <div class="max-w-[82%]">
                                        <div class="mb-1 flex items-center gap-2 text-xs {{ $msg['mine'] ? 'justify-end' : '' }}">
                                            @if (! $msg['mine'])
                                                <span class="text-blue-100/70">{{ $msg['sender_name'] }}</span>
                                            @else
                                                <span class="text-blue-100/70">You</span>
                                            @endif

                                            <span class="text-blue-100/40">{{ $msg['time'] }}</span>
                                        </div>

                                        @if ($msg['text'])
                                            <div class="rounded-2xl px-4 py-3 text-sm leading-relaxed shadow-sm {{ $msg['mine'] ? 'rounded-br-sm bg-cyan-500 text-white' : 'rounded-bl-sm border border-white/10 bg-white/10 text-blue-50' }}">
                                                {!! nl2br(e($msg['text'])) !!}
                                            </div>
                                        @endif

                                        @if (count($msg['attachments']))
                                            <div class="mt-2 flex max-w-md flex-wrap gap-2 {{ $msg['mine'] ? 'justify-end' : 'justify-start' }}">
                                                @foreach ($msg['attachments'] as $attachment)
                                                    <div class="group relative w-36 overflow-hidden rounded-xl border border-white/10 bg-white/6">
                                                        @if ($attachment['is_image'])
                                                            <a href="#"
                                                                @click.prevent="lightbox = true; lightboxSrc = '{{ $attachment['url'] }}'; lightboxName = '{{ str_replace("'", "\\'", $attachment['name']) }}'"
                                                                class="block h-28 w-full cursor-zoom-in overflow-hidden">
                                                                <img src="{{ $attachment['url'] }}"
                                                                    class="h-28 w-full object-cover transition group-hover:scale-105"
                                                                    alt="{{ $attachment['name'] }}">
                                                            </a>
                                                        @else
                                                            <a href="{{ $attachment['url'] }}" target="_blank"
                                                                rel="noopener"
                                                                class="flex h-28 w-full flex-col items-center justify-center gap-1 p-2 text-center transition hover:bg-white/5">
                                                                <span class="material-symbols-outlined text-3xl text-blue-100/50">description</span>
                                                                <span class="line-clamp-2 break-all text-[11px] font-medium text-blue-100/80">
                                                                    {{ $attachment['name'] }}
                                                                </span>
                                                                <span class="text-[10px] font-semibold uppercase tracking-wide text-cyan-200">View</span>
                                                            </a>
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
                                        <span class="material-symbols-outlined mx-auto mb-2 block text-4xl text-white/30">forum</span>
                                        <p class="text-sm font-medium text-white/70">No messages yet.</p>
                                        <p class="mt-1 text-xs text-white/40">Start the conversation with the team!</p>
                                    </div>
                                </div>
                            @endforelse
                        </div>

                        {{-- Pending Attachments Preview --}}
                        @if (count($files))
                            <div class="flex flex-wrap gap-2 border-t border-white/10 px-5 pt-3">
                                @foreach ($files as $index => $file)
                                    <span class="relative inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/6 py-1.5 pl-2 pr-8 text-xs text-blue-100/70">
                                        @if (str_starts_with((string) $file->getMimeType(), 'image/'))
                                            <img src="{{ $file->temporaryUrl() }}" class="h-8 w-8 rounded-lg object-cover" alt="">
                                        @else
                                            <span class="material-symbols-outlined text-lg text-white/50">description</span>
                                        @endif

                                        <span class="max-w-32 truncate">{{ $file->getClientOriginalName() }}</span>

                                        <button type="button" wire:click="removeFile({{ $index }})"
                                            class="absolute right-1 top-1/2 -translate-y-1/2 cursor-pointer text-white/40 transition hover:text-red-400">
                                            <span class="material-symbols-outlined text-sm">close</span>
                                        </button>
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        {{-- Composer --}}
                        <form wire:submit.prevent="sendMessage" class="border-t border-white/10 px-4 py-3">
                            @error('message')
                                <p class="mb-2 px-1 text-xs font-medium text-red-400">{{ $message }}</p>
                            @enderror

                            <div class="flex items-end gap-2">
                                <label
                                    class="flex h-11 w-11 shrink-0 cursor-pointer items-center justify-center rounded-full border border-white/10 bg-white/8 text-white/50 transition hover:bg-white/12 hover:text-white/80">
                                    <span class="material-symbols-outlined">attach_file</span>

                                    <input type="file" wire:model="files" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.zip,image/*" class="hidden">

                                    <span wire:loading wire:target="files" class="hidden"></span>
                                </label>

                                <textarea wire:model="message" rows="1" placeholder="Type a message..."
                                    class="no-scrollbar max-h-32 min-h-11 flex-1 resize-none rounded-2xl border border-white/10 bg-white/6 px-4 py-2.5 text-sm text-white placeholder:text-white/30 focus:border-cyan-300/30 focus:bg-white/8 focus:outline-none focus:ring-2 focus:ring-cyan-300/10"
                                    x-on:input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 128) + 'px'"></textarea>

                                <button type="submit"
                                    class="flex h-11 w-11 shrink-0 cursor-pointer items-center justify-center rounded-full bg-cyan-500 text-white shadow-sm transition hover:bg-cyan-400 disabled:opacity-60"
                                    wire:loading.attr="disabled" wire:target="sendMessage">
                                    <span class="material-symbols-outlined" wire:loading.remove wire:target="sendMessage">send</span>
                                    <span class="material-symbols-outlined animate-spin hidden" wire:loading wire:target="sendMessage">progress_activity</span>
                                </button>
                            </div>

                            @error('files.*')
                                <p class="mt-2 px-1 text-xs font-medium text-red-400">{{ $message }}</p>
                            @enderror
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Image Lightbox --}}
    <div x-cloak x-show="lightbox" @keydown.escape.window="lightbox = false"
        @click="lightbox = false"
        x-transition.opacity
        style="display: none;"
        class="fixed inset-0 z-9999 flex items-center justify-center bg-black/85 p-4 backdrop-blur-sm">
        <button type="button" @click.stop="lightbox = false"
            class="absolute right-4 top-4 flex h-11 w-11 cursor-pointer items-center justify-center rounded-full border border-white/15 bg-white/10 text-white transition hover:bg-white/20">
            <span class="material-symbols-outlined text-2xl">close</span>
        </button>

        <figure class="max-h-full max-w-full" @click.stop>
            <img :src="lightboxSrc" :alt="lightboxName"
                class="max-h-[88vh] max-w-full rounded-2xl object-contain shadow-2xl">
            <figcaption class="mt-3 truncate text-center text-sm text-blue-100/70" x-text="lightboxName"></figcaption>
        </figure>
    </div>
</div>