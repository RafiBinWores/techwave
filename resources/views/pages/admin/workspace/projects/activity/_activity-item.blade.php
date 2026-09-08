@php($update = $node['update'])
@php($depth = $depth ?? 0)

<div class="relative flex gap-3">
    @if ($depth === 0)
    <div class="relative z-10 flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white">
        <span class="material-symbols-outlined text-[14px]
                @if (($update->type ?? 'update') === 'comment') text-slate-400
                @elseif ($update->health === 'off_track') text-red-500
                @elseif ($update->health === 'at_risk') text-amber-500
                @else text-emerald-500
                @endif">
            {{ ($update->type ?? 'update') === 'comment' ? 'comment' : 'show_chart' }}
        </span>
    </div>
    @else
    <div class="w-7 shrink-0"></div>
    @endif

    <div class="min-w-0 w-full pt-0.5" x-data="{ open: false }">
        <p class="text-xs leading-5 text-slate-600">
            <span class="font-medium text-slate-800">
                {{ $update->author?->name ?: 'Former team member' }}
            </span>
            @if (($update->type ?? 'update') === 'update')
            posted a project update
            <span class="rounded-full px-2 py-1 align-middle text-[10px] font-medium ring-1 ring-inset {{ $this->healthBadge($update->health ?? 'on_track') }}">
                {{ $update->health === 'off_track' ? 'Off track' : ($update->health === 'at_risk' ? 'At risk' : 'On track') }}
            </span>
            @else
            {{ $depth === 0 ? 'commented on the project' : 'replied in thread' }}
            @endif
        </p>

        <p class="mt-1 text-xs leading-5 text-slate-500">
            {{ $update->body }}
        </p>

        @if ($update->attachments)
        <div class="mt-2 flex flex-wrap gap-1.5">
            @foreach ($update->attachments as $attachment)
            <a
                href="{{ $attachment['url'] }}"
                target="_blank"
                rel="noopener"
                class="inline-flex max-w-full items-center gap-1.5 rounded-md border border-slate-200 bg-white px-2 py-1 text-[11px] text-slate-600 transition hover:border-slate-300 hover:bg-slate-50">
                <span class="material-symbols-outlined text-[13px] text-slate-400">
                    {{ str_starts_with($attachment['mime'] ?? '', 'image/') ? 'image' : 'insert_drive_file' }}
                </span>
                <span class="truncate">{{ $attachment['name'] }}</span>
            </a>
            @endforeach
        </div>
        @endif

        <div class="mt-1 flex items-center gap-3">
            <span class="text-[11px] text-slate-400">
                {{ $update->created_at?->format('M d, Y g:i A') }}
            </span>

            @if ($this->canWriteUpdate())
            <button
                type="button"
                @click="$wire.startReply({{ $update->id }})"
                class="cursor-pointer text-[11px] font-medium text-slate-400 transition hover:text-slate-700">
                Reply
            </button>

            @if (count($node['replies']))
            <button
                type="button"
                @click="open = !open"
                class="cursor-pointer text-[11px] font-medium text-slate-400 transition hover:text-slate-700">
                <span x-show="!open">{{ count($node['replies']) }} {{ Str::plural('reply', count($node['replies'])) }}</span>
                <span x-show="open" x-cloak>Hide replies</span>
            </button>
            @endif
            @endif
        </div>

        @if ($this->canWriteUpdate() && $this->replyToId === $update->getKey())
        <form wire:submit.prevent="postReply" class="mt-3 rounded-lg border border-slate-200 bg-white p-2">
            <textarea
                wire:model="replyBody"
                rows="2"
                maxlength="5000"
                placeholder="Write a reply..."
                class="w-full resize-none bg-transparent text-xs leading-5 text-slate-700 outline-none placeholder:text-slate-300"></textarea>

            <div class="mt-2 flex items-center justify-between gap-3">
                @error('replyBody')
                <p class="text-[11px] text-red-500">{{ $message }}</p>
                @enderror

                <div class="ml-auto flex items-center gap-2">
                    <button
                        type="button"
                        wire:click="cancelReply"
                        class="h-7 cursor-pointer rounded-md px-2.5 text-[11px] font-medium text-slate-500 transition hover:bg-slate-100">
                        Cancel
                    </button>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="postReply"
                        class="inline-flex h-7 items-center gap-1 rounded-md bg-slate-900 px-3 text-[11px] font-medium text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60">
                        <span wire:loading.remove wire:target="postReply">Post</span>
                        <span wire:loading wire:target="postReply" class="h-3 w-3 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                    </button>
                </div>
            </div>
        </form>
        @endif

        @if (count($node['replies']))
        <div x-show="open" x-cloak class="mt-3 space-y-4 border-l border-slate-100 pl-4">
            @foreach ($node['replies'] as $child)
            <div wire:key="activity-reply-{{ $child['update']->id }}">
                @include('pages.admin.workspace.projects.activity._activity-item', ['node' => $child, 'depth' => $depth + 1])
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>