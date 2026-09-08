{{-- Edit project modal --}}
@if ($showEditModal)
<div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-slate-900/40" wire:click="$set('showEditModal', false)"></div>

    <div class="relative mx-auto my-10 w-full max-w-5xl rounded-2xl bg-white shadow-2xl">
        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-slate-200 px-8 py-5">
            <div>
                <h3 class="text-lg font-semibold text-on-surface">Edit Project</h3>
                <p class="mt-0.5 text-sm text-secondary">Update the project in sections — changes apply when you save.</p>
            </div>

            <button type="button" wire:click="$set('showEditModal', false)"
                class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 cursor-pointer">
                <span class="material-symbols-outlined text-[22px]">close</span>
            </button>
        </div>

        <form wire:submit.prevent="saveProjectEdit" class="max-h-[calc(100vh-9rem)] overflow-y-auto">
            {{-- Icon selector (top) --}}
            <div class="px-8 pt-2">
                <div x-data="{ open: false, search: '' }" @click.outside="open = false" class="relative flex items-center gap-3">
                    <button type="button" @click="open = !open"
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border transition hover:scale-105 cursor-pointer"
                        style="background-color: {{ $this->editIconColor() }}1f; border-color: {{ $this->editIconColor() }}40; color: {{ $this->editIconColor() }}"
                        title="Choose icon & color">
                        <span class="material-symbols-outlined text-[24px]">
                            {{ $this->editIcon ?: 'space_dashboard' }}
                        </span>
                    </button>

                    <div class="min-w-0 flex-1">
                        @if ($this->editIcon)
                        <p class="truncate text-sm font-medium text-on-surface">
                            {{ $this->editIcon }}
                        </p>
                        <button type="button" @click="$wire.set('editIcon', '')"
                            class="mt-1 cursor-pointer text-xs text-slate-400 transition hover:text-red-500">
                            Change icon
                        </button>
                        @else
                        <p class="text-sm text-slate-400">Choose an icon & color</p>
                        @endif
                    </div>

                    <div x-cloak x-show="open" x-transition
                        class="absolute left-0 right-0 top-0 z-40 mt-1.5 max-w-md overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl">
                        <div class="sticky top-0 border-b border-slate-100 bg-white p-2">
                            <input x-model="search" type="text" placeholder="Search icons..."
                                class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm outline-none focus:border-primary" />
                        </div>

                        {{-- Icon color --}}
                        <div class="border-b border-slate-100 p-3">
                            <p class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                                Icon color
                            </p>
                            <div class="flex flex-wrap items-center gap-1.5">
                                <input type="color"
                                    :value="($wire.editIconColor || '#4f46e5')"
                                    @input="$wire.set('editIconColor', $event.target.value)"
                                    class="h-6 w-10 cursor-pointer rounded-full border border-black/10 bg-transparent p-0"
                                    title="Custom color" />
                                <span class="mr-1 h-4 w-px bg-slate-100"></span>
                                @foreach ($this->projectIconColors() as $color)
                                <button type="button"
                                    @click="$wire.set('editIconColor', @js($color))"
                                    wire:key="color-{{ $color }}"
                                    @class([ 'h-6 w-6 rounded-full border border-black/10 transition cursor-pointer' , 'ring-2 ring-primary ring-offset-2'=> $this->editIconColor === $color,
                                    'hover:scale-110' => $this->editIconColor !== $color,
                                    ])
                                    style="background-color: {{ $color }}"
                                    title="{{ $color }}"></button>
                                @endforeach
                            </div>
                        </div>

                        <div class="grid max-h-100 grid-cols-6 gap-1 overflow-y-auto p-2">
                            @foreach ($this->projectIcons() as $icon)
                            <button type="button"
                                @click="open = false; search = ''; $wire.set('editIcon', @js($icon))"
                                wire:key="icon-{{ $icon }}"
                                x-show="!search || @js($icon).includes(search.toLowerCase())"
                                @class([ 'flex h-10 w-10 items-center justify-center rounded-lg border transition cursor-pointer' , 'border-primary bg-primary/10'=> $this->editIcon === $icon,
                                'border-transparent hover:border-slate-200 hover:bg-slate-50' => $this->editIcon !== $icon,
                                ])>
                                <span class="material-symbols-outlined text-[20px]" style="color: {{ $this->editIconColor() }}">{{ $icon }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>

                    @error('editIcon')
                    <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                    @error('editIconColor')
                    <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Title --}}
            <div class="px-8 pt-6">
                <input
                    type="text"
                    wire:model.live="editTitle"
                    maxlength="180"
                    placeholder="Name your project..."
                    class="w-full bg-transparent text-2xl font-bold text-on-surface placeholder:text-slate-300 outline-none focus:ring-0" />

                @error('editTitle')
                <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Summary --}}
            <div class="px-8 pt-3">
                <div x-data="{ chars: 0 }" class="relative">
                    <textarea
                        wire:model.live="editSummary"
                        @input="chars = $el.value.length; const lh = parseFloat(getComputedStyle($el).lineHeight) || 24; const lines = Math.max(1, Math.round($el.scrollHeight / lh)); $el.style.height = (lines * lh) + 'px'"
                        @keydown.enter.prevent
                        placeholder="Add a short summary of what this project is about..."
                        maxlength="300"
                        class="w-full bg-transparent p-0 text-sm text-on-surface placeholder:text-slate-300 outline-none focus:ring-0 resize-none overflow-hidden"
                        style="height: 1.5rem; min-height: 1.5rem; line-height: 1.5rem;"
                        spellcheck="false"></textarea>

                    <div x-show="chars >= 300" class="absolute right-2 bottom-1 text-[10px] text-red-500 font-medium">
                        <span x-text="chars"></span>/300 chars
                    </div>

                    <div x-show="chars >= 300" class="text-[10px] text-red-500">
                        Maximum 300 characters reached
                    </div>

                    @error('editSummary')
                    <p class="text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Linear-style property row (single line, small text, no labels) --}}
            <div class="flex flex-wrap items-center gap-x-3 gap-y-2 border-b border-slate-100 px-8 py-3 text-sm text-slate-600">
                {{-- Status --}}
                <span class="relative">
                    <select wire:model.live="editStatus"
                        class="h-7 appearance-none rounded-md border border-slate-200 bg-white pl-1.5 pr-6 text-xs font-medium text-on-surface outline-none transition hover:border-primary/40 focus:border-primary">
                        @foreach (\App\Enums\ProjectStatus::cases() as $statusOption)
                        <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                        @endforeach
                    </select>
                    <span class="material-symbols-outlined pointer-events-none absolute right-1 top-1/2 -translate-y-1/2 text-[14px] text-slate-400">expand_more</span>
                </span>

                {{-- Priority --}}
                <span class="relative">
                    <select wire:model.live="editPriority"
                        class="h-7 appearance-none rounded-md border border-slate-200 bg-white pl-1.5 pr-6 text-xs font-medium text-on-surface outline-none transition hover:border-primary/40 focus:border-primary">
                        @foreach (\App\Enums\ProjectPriority::cases() as $priorityOption)
                        <option value="{{ $priorityOption->value }}">{{ $priorityOption->label() }}</option>
                        @endforeach
                    </select>
                    <span class="material-symbols-outlined pointer-events-none absolute right-1 top-1/2 -translate-y-1/2 text-[14px] text-slate-400">expand_more</span>
                </span>

                {{-- Label (type to create, pick existing) --}}
                <span class="relative" x-data="{ open: false, search: @js($this->editLabel), color: @js($this->editLabelColor), colors: @js(\App\Models\WorkspaceLabel::colorOptions()), labels: @js($this->existingLabels()), filteredLabels() { const q = (this.search || '').toLowerCase().trim(); const list = q ? this.labels.filter(l => l.name.toLowerCase().includes(q)) : this.labels; return list.slice(0, 20); }, isNewLabel() { const q = (this.search || '').trim(); return q.length > 0 && !this.labels.some(l => l.name.toLowerCase() === q.toLowerCase()); } }" @click.outside="open = false">
                    <div class="relative">
                        <input type="text" x-model="search" wire:model.live="editLabel" placeholder="Label"
                            @focus="open = true"
                            @input="open = true"
                            class="h-7 w-32 rounded-md border border-slate-200 bg-white px-2 pr-6 text-xs font-medium text-on-surface placeholder:text-slate-400 outline-none transition hover:border-primary/40 focus:border-primary" />
                        <span class="material-symbols-outlined pointer-events-none absolute right-1 top-1/2 -translate-y-1/2 text-[14px] text-slate-400">expand_more</span>
                    </div>

                    <div x-cloak x-show="open && (filteredLabels().length > 0 || isNewLabel())" x-transition
                        class="absolute left-0 z-30 mt-1 w-56 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
                        <div x-show="filteredLabels().length > 0" class="max-h-44 overflow-y-auto p-1">
                            <template x-for="label in filteredLabels()" :key="label.name">
                                <button type="button"
                                    @click="$wire.set('editLabel', label.name); $wire.set('editLabelColor', label.color); open = false; search = label.name; color = label.color"
                                    class="flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-left text-xs text-on-surface transition hover:bg-slate-50">
                                    <span class="h-2 w-2 shrink-0 rounded-full" :style="`background-color: ${label.hex}`"></span>
                                    <span class="flex-1 truncate" x-text="label.name"></span>
                                    <span x-show="label.name.trim().toLowerCase() === (search || '').trim().toLowerCase()" class="material-symbols-outlined text-[14px] text-primary">check</span>
                                </button>
                            </template>
                        </div>

                        <div x-show="isNewLabel()" class="border-t border-slate-100 p-2">
                            <div class="mb-1.5 text-[10px] font-medium uppercase tracking-wider text-slate-400">New label color</div>
                            <div class="flex w-full flex-wrap gap-1.5">
                                <template x-for="c in colors" :key="c.name">
                                    <button type="button" @click="$wire.set('editLabelColor', c.name); color = c.name; open = false"
                                        class="flex h-5 w-5 items-center justify-center rounded-full transition hover:scale-110"
                                        :style="`background-color: ${c.hex}`">
                                        <span x-show="color === c.name" class="material-symbols-outlined text-[12px] text-white">check</span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    @error('editLabel')
                    <p class="absolute left-0 top-full mt-0.5 text-[10px] text-red-500">{{ $message }}</p>
                    @enderror
                </span>

                {{-- Team lead (searchable) --}}
                <span class="relative" x-data="{ open: false, search: '' }" @click.outside="open = false">
                    <button type="button" @click="open = !open"
                        class="flex h-7 max-w-[140px] items-center gap-1 rounded-md border border-slate-200 bg-white pl-1.5 pr-5 text-xs font-medium text-on-surface outline-none transition hover:border-primary/40 focus:border-primary">
                        <span class="truncate">
                            {{ $this->selectedManagerName() ?: 'Team Lead' }}
                        </span>
                        <span class="material-symbols-outlined pointer-events-none absolute right-1 top-1/2 -translate-y-1/2 text-[10px] text-slate-400" style="font-variation-settings: 'FILL' 0, 'wght' 300, 'GRAD' 0, 'opsz' 16">expand_more</span>
                    </button>

                    <div x-cloak x-show="open" x-transition
                        class="absolute left-0 z-30 mt-1 w-56 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
                        <div class="border-b border-slate-100 p-2">
                            <input x-model="search" type="text" placeholder="Search team lead..."
                                class="w-full rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs outline-none focus:border-primary" />
                        </div>

                        <div class="max-h-44 overflow-y-auto p-1">
                            <button type="button" wire:click="$set('editProjectManagerId', null)"
                                x-show="!search"
                                class="flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-left text-xs text-on-surface transition hover:bg-slate-50">
                                <span class="material-symbols-outlined text-[15px] text-slate-400">person_off</span>
                                None
                            </button>

                            @foreach ($this->staff() as $user)
                            <button type="button"
                                wire:click="$set('editProjectManagerId', {{ $user->id }}), open = false"
                                wire:key="manager-{{ $user->id }}"
                                x-show="!search || '{{ strtolower($user->name) }}'.includes(search.toLowerCase())"
                                @class([ 'flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-left text-xs transition hover:bg-slate-50' , 'bg-primary/5'=> (int) $this->editProjectManagerId === (int) $user->id,
                                ])>
                                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-primary/10 text-[9px] font-bold text-primary">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </span>
                                <span class="flex-1 truncate text-on-surface">{{ $user->name }}</span>
                                @if ((int) $this->editProjectManagerId === (int) $user->id)
                                <span class="material-symbols-outlined text-[14px] text-primary">check</span>
                                @endif
                            </button>
                            @endforeach
                        </div>
                    </div>

                    @error('editProjectManagerId')
                    <p class="absolute left-0 top-full mt-0.5 text-[10px] text-red-500">{{ $message }}</p>
                    @enderror
                </span>

                {{-- Client (searchable) --}}
                <span class="relative" x-data="{ open: false, search: '' }" @click.outside="open = false">
                    <button type="button" @click="open = !open"
                        class="flex h-7 max-w-[140px] items-center gap-1 rounded-md border border-slate-200 bg-white pl-1.5 pr-5 text-xs font-medium text-on-surface outline-none transition hover:border-primary/40 focus:border-primary">
                        <span class="truncate">
                            {{ $this->selectedClientName() ?: 'Client' }}
                        </span>
                        <span class="material-symbols-outlined pointer-events-none absolute right-1 top-1/2 -translate-y-1/2 text-[10px] text-slate-400" style="font-variation-settings: 'FILL' 0, 'wght' 300, 'GRAD' 0, 'opsz' 16">expand_more</span>
                    </button>

                    <div x-cloak x-show="open" x-transition
                        class="absolute left-0 z-30 mt-1 w-56 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
                        <div class="border-b border-slate-100 p-2">
                            <input x-model="search" type="text" placeholder="Search client..."
                                class="w-full rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs outline-none focus:border-primary" />
                        </div>

                        <div class="max-h-44 overflow-y-auto p-1">
                            <button type="button" wire:click="$set('editClientId', null)"
                                x-show="!search"
                                class="flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-left text-xs text-on-surface transition hover:bg-slate-50">
                                <span class="material-symbols-outlined text-[15px] text-slate-400">person_off</span>
                                None
                            </button>

                            @foreach ($this->clients() as $client)
                            <button type="button"
                                wire:click="$set('editClientId', {{ $client->id }}), open = false"
                                wire:key="client-{{ $client->id }}"
                                x-show="!search || '{{ strtolower($client->name) }}'.includes(search.toLowerCase())"
                                @class([ 'flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-left text-xs transition hover:bg-slate-50' , 'bg-primary/5'=> (int) $this->editClientId === (int) $client->id,
                                ])>
                                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-primary/10 text-[9px] font-bold text-primary">
                                    {{ strtoupper(substr($client->name, 0, 1)) }}
                                </span>
                                <span class="flex-1 truncate text-on-surface">{{ $client->name }}</span>
                                @if ((int) $this->editClientId === (int) $client->id)
                                <span class="material-symbols-outlined text-[14px] text-primary">check</span>
                                @endif
                            </button>
                            @endforeach
                        </div>
                    </div>

                    @error('editClientId')
                    <p class="absolute left-0 top-full mt-0.5 text-[10px] text-red-500">{{ $message }}</p>
                    @enderror
                </span>

                {{-- Members (searchable multi-select) --}}
                <span class="relative" x-data="{ open: false, search: '' }" @click.outside="open = false">
                    <button type="button" @click="open = !open"
                        class="flex h-7 max-w-[140px] items-center gap-1 rounded-md border border-slate-200 bg-white pl-1.5 pr-5 text-xs font-medium text-on-surface outline-none transition hover:border-primary/40 focus:border-primary">
                        <span class="truncate">
                            {{ $this->editMemberIds ? count($this->editMemberIds).' member'.(count($this->editMemberIds) > 1 ? 's' : '') : 'Members' }}
                        </span>
                        <span class="material-symbols-outlined pointer-events-none absolute right-1 top-1/2 -translate-y-1/2 text-[10px] text-slate-400" style="font-variation-settings: 'FILL' 0, 'wght' 300, 'GRAD' 0, 'opsz' 16">expand_more</span>
                    </button>

                    <div x-cloak x-show="open" x-transition
                        class="absolute left-0 z-30 mt-1 w-56 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
                        <div class="border-b border-slate-100 p-2">
                            <input x-model="search" type="text" placeholder="Search members..."
                                class="w-full rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs outline-none focus:border-primary" />
                        </div>

                        <div class="max-h-44 overflow-y-auto p-1">
                            @foreach ($this->staff() as $user)
                            <button
                                type="button"
                                wire:key="member-{{ $user->id }}"
                                wire:click="toggleMember({{ $user->id }})"
                                x-show="!search || '{{ strtolower($user->name) }}'.includes(search.toLowerCase())"
                                @class([ 'flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-left text-xs transition hover:bg-slate-50' , 'bg-primary/5'=> in_array((int) $user->id, $this->editMemberIds),
                                ])>
                                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-primary/10 text-[9px] font-bold text-primary">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </span>
                                <span class="flex-1 truncate text-on-surface">{{ $user->name }}</span>
                                @if (in_array((int) $user->id, $this->editMemberIds))
                                <span class="material-symbols-outlined text-[14px] text-primary">check</span>
                                @endif
                            </button>
                            @endforeach
                        </div>
                    </div>

                    @error('editMemberIds')
                    <p class="absolute left-0 top-full mt-0.5 text-[10px] text-red-500">{{ $message }}</p>
                    @enderror
                </span>

                {{-- Start date --}}
                <span class="relative flex items-center gap-1">
                    <span class="text-[10px] font-medium text-slate-400 uppercase tracking-wider">Start</span>
                    <input type="date" wire:model.live="editStartDate"
                        class="h-7 rounded-md border border-slate-200 bg-white px-2 text-xs font-medium text-on-surface outline-none transition hover:border-primary/40 focus:border-primary" />

                    @error('editStartDate')
                    <p class="absolute left-0 top-full mt-0.5 text-[10px] text-red-500">{{ $message }}</p>
                    @enderror
                </span>

                {{-- Target date --}}
                <span class="relative flex items-center gap-1">
                    <span class="text-[10px] font-medium text-slate-400 uppercase tracking-wider">End</span>
                    <input type="date" wire:model.live="editTargetDate"
                        class="h-7 rounded-md border border-slate-200 bg-white px-2 text-xs font-medium text-on-surface outline-none transition hover:border-primary/40 focus:border-primary" />

                    @error('editTargetDate')
                    <p class="absolute left-0 top-full mt-0.5 text-[10px] text-red-500">{{ $message }}</p>
                    @enderror
                </span>
            </div>

            {{-- Body: Linear-style project details editor --}}
            <div class="px-8 pb-6 mt-3">
                @include('pages.admin.workspace.partials._editor-field', ['editorContent' => $editDetails, 'editorSyncProperty' => 'editDetails', 'editorErrorProperty' => 'editDetails'])
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50/60 px-8 py-4">
                <button type="button" wire:click="$set('showEditModal', false)"
                    class="rounded-lg px-4 py-2 text-sm font-medium text-on-surface transition-colors hover:bg-slate-100 cursor-pointer">
                    Cancel
                </button>

                <button type="submit" wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-2 text-sm font-medium text-white transition-opacity hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60 cursor-pointer">
                    <span wire:loading.remove wire:target="saveProjectEdit">Save changes</span>
                    <span wire:loading wire:target="saveProjectEdit" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                </button>
            </div>
        </form>
    </div>
</div>
@endif