    {{-- New issue modal --}}
    @if ($showIssueModal)
    <div class="fixed inset-0 z-[70] overflow-y-auto">
        <div
            class="fixed inset-0 bg-slate-950/35 backdrop-blur-[1px]"
            wire:click="closeIssueModal">
        </div>

        <div class="relative mx-auto my-10 max-w-3xl overflow-hidden rounded-2xl bg-white border border-slate-200 shadow-2xl">
            <form wire:submit.prevent="saveIssue">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">New issue</h3>
                        <p class="mt-0.5 text-xs text-slate-400">Track a task in {{ $project->name }}.</p>
                    </div>

                    <button
                        type="button"
                        wire:click="closeIssueModal"
                        class="flex h-7 w-7 items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>

                <div class="max-h-[70vh] overflow-y-auto px-5 py-5">
                    {{-- Title & summary --}}
                    <div>
                        <input
                            type="text"
                            wire:model.live="issueTitle"
                            maxlength="255"
                            autofocus
                            placeholder="Name your issue..."
                            class="w-full bg-transparent text-2xl font-bold text-on-surface outline-none placeholder:text-slate-300 focus:ring-0" />

                        @error('issueTitle')
                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-3">
                        <textarea
                            wire:model.live="issueSummary"
                            rows="2"
                            maxlength="10000"
                            placeholder="What is this issue about?"
                            class="w-full resize-none bg-transparent text-sm text-on-surface placeholder:text-slate-300 outline-none focus:ring-0"></textarea>

                        @error('issueSummary')
                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Attachments --}}
                    @if ($this->issueFiles)
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        @foreach ($this->issueFiles as $index => $file)
                        <span wire:key="issue-file-{{ $index }}" class="inline-flex max-w-full items-center gap-1.5 rounded-md border border-slate-200 bg-slate-50 px-2 py-1 text-[11px] text-slate-600">
                            <span class="material-symbols-outlined text-[13px] text-slate-400">insert_drive_file</span>
                            <span class="truncate">{{ $file->getClientOriginalName() }}</span>
                            <button
                                type="button"
                                @click="$wire.removeIssueFile({{ $index }})"
                                class="flex h-4 w-4 shrink-0 cursor-pointer items-center justify-center rounded text-slate-400 transition hover:bg-slate-200 hover:text-slate-700">
                                <span class="material-symbols-outlined text-[12px]">close</span>
                            </button>
                        </span>
                        @endforeach
                    </div>
                    @endif

                    @error('issueFiles.*')
                    <p class="mt-2 text-[11px] text-red-500">{{ $message }}</p>
                    @enderror

                    {{-- Properties --}}
                    <div class="mt-5 border-t border-slate-100 pt-4">
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-2 text-sm text-slate-600">
                            {{-- Status --}}
                            <span class="relative">
                                <select wire:model.live="issueStatus"
                                    class="h-7 appearance-none rounded-md border border-slate-200 bg-white pl-1.5 pr-6 text-xs font-medium text-on-surface outline-none transition hover:border-primary/40 focus:border-primary">
                                    @foreach (\App\Enums\TaskStatus::cases() as $statusOption)
                                    <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                                    @endforeach
                                </select>
                                <span class="material-symbols-outlined pointer-events-none absolute right-1 top-1/2 -translate-y-1/2 text-[14px] text-slate-400">expand_more</span>
                            </span>

                            {{-- Priority --}}
                            <span class="relative">
                                <select wire:model.live="issuePriority"
                                    class="h-7 appearance-none rounded-md border border-slate-200 bg-white pl-1.5 pr-6 text-xs font-medium text-on-surface outline-none transition hover:border-primary/40 focus:border-primary">
                                    @foreach (\App\Enums\TaskPriority::cases() as $priorityOption)
                                    <option value="{{ $priorityOption->value }}">{{ $priorityOption->label() }}</option>
                                    @endforeach
                                </select>
                                <span class="material-symbols-outlined pointer-events-none absolute right-1 top-1/2 -translate-y-1/2 text-[14px] text-slate-400">expand_more</span>
                            </span>

                            {{-- Assignee --}}
                            <span class="relative">
                                <select wire:model.live="issueAssigneeId"
                                    class="h-7 appearance-none rounded-md border border-slate-200 bg-white pl-1.5 pr-6 text-xs font-medium text-on-surface outline-none transition hover:border-primary/40 focus:border-primary">
                                    <option value="">None</option>

                                    @foreach ($this->projectMembers() as $member)
                                    <option value="{{ $member->id }}">{{ $member->name }}</option>
                                    @endforeach
                                </select>
                                <span class="material-symbols-outlined pointer-events-none absolute right-1 top-1/2 -translate-y-1/2 text-[14px] text-slate-400">expand_more</span>
                            </span>

                            {{-- Label --}}
                            <span class="relative" x-data="{ open: false, labels: @js($this->projectLabels()->map(fn ($label) => ['id' => $label->id, 'name' => $label->name, 'hex' => $this->labelHex($label->color)])->values()->all()) }" @click.outside="open = false">
                                <button type="button" @click="open = !open"
                                    class="flex h-7 items-center gap-1.5 rounded-md border border-slate-200 bg-white pl-1.5 pr-5 text-xs font-medium text-on-surface outline-none transition hover:border-primary/40 focus:border-primary">
                                    <span class="h-2 w-2 rounded-full" :style="`background-color: ${labels.find(l => l.id === @js($this->issueLabelId))?.hex || '#cbd5e1'}`"></span>
                                    <span class="truncate">{{ $this->selectedLabelName() ?: 'Label' }}</span>
                                    <span class="material-symbols-outlined pointer-events-none absolute right-1 top-1/2 -translate-y-1/2 text-[12px] text-slate-400">expand_more</span>
                                </button>

                                <div x-cloak x-show="open" x-transition
                                    class="absolute left-0 z-30 mt-1 w-56 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
                                    <div class="max-h-44 overflow-y-auto p-1">
                                        <button type="button" @click="$wire.set('issueLabelId', ''); open = false"
                                            class="flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-left text-xs text-slate-500 transition hover:bg-slate-50">
                                            <span class="h-2 w-2 shrink-0 rounded-full bg-slate-300"></span>
                                            <span class="flex-1">None</span>
                                        </button>

                                        <template x-for="label in labels" :key="label.id">
                                            <button type="button" @click="$wire.set('issueLabelId', label.id); open = false"
                                                class="flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-left text-xs text-on-surface transition hover:bg-slate-50">
                                                <span class="h-2 w-2 shrink-0 rounded-full" :style="`background-color: ${label.hex}`"></span>
                                                <span class="flex-1 truncate" x-text="label.name"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </span>

                            {{-- Due date --}}
                            <span class="relative flex items-center gap-1">
                                <span class="text-[10px] font-medium text-slate-400 uppercase tracking-wider">Due date</span>
                                <input type="date" wire:model.live="issueDueDate"
                                    class="h-7 rounded-md border border-slate-200 bg-white px-2 text-xs font-medium text-on-surface outline-none transition hover:border-primary/40 focus:border-primary" />
                            </span>
                        </div>

                        <div class="mt-2 space-y-1">
                            @error('issueAssigneeId')
                            <p class="text-[11px] text-red-500">{{ $message }}</p>
                            @enderror

                            @error('issueLabelId')
                            <p class="text-[11px] text-red-500">{{ $message }}</p>
                            @enderror

                            @error('issueDueDate')
                            <p class="text-[11px] text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-slate-200 bg-slate-50/60 px-5 py-3.5">
                    <label class="mr-auto flex h-8 cursor-pointer items-center gap-1 rounded-md px-2 text-xs font-medium text-slate-500 transition hover:bg-slate-100">
                        <span class="material-symbols-outlined text-[15px]">attach_file</span>
                        Attach
                        <input type="file" wire:model="issueFiles" multiple class="sr-only" />
                    </label>

                    <button
                        type="button"
                        wire:click="closeIssueModal"
                        class="h-8 rounded-md px-3 text-xs font-medium text-slate-600 transition hover:bg-slate-100">
                        Cancel
                    </button>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="saveIssue"
                        class="inline-flex h-8 items-center gap-1.5 rounded-md bg-slate-900 px-3 text-xs font-medium text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveIssue">Create issue</span>
                        <span wire:loading wire:target="saveIssue" class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif