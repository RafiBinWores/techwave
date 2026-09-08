                    <div class="space-y-3">
                        <div class="flex items-center justify-between gap-4">
                            <p class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-secondary">
                                <span class="material-symbols-outlined text-[16px]">description</span>
                                Project details
                            </p>

                            <span class="text-[11px] text-slate-400">Type <kbd class="rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 font-mono text-[10px]">/</kbd> for commands</span>
                        </div>

                        <div
                            x-data="projectDetailsEditor($wire, @js($editorContent ?? null), @js($editorSyncProperty ?? 'formDetails'))"
                            x-on:click.outside="closeSlashMenu(); closeLinkBar()"
                            class="relative"
                            wire:ignore>

                            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white transition focus-within:border-primary/40 focus-within:ring-4 focus-within:ring-primary/5">
                                <div x-show="loading" class="flex min-h-[170px] items-center justify-center gap-2 text-sm text-slate-400">
                                    <span class="h-4 w-4 animate-spin rounded-full border-2 border-slate-200 border-t-primary"></span>
                                    Loading editor...
                                </div>

                                <div x-show="!loading" x-cloak>
                                    <div x-ref="editor" class="linear-project-editor"></div>

                                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/40 px-3 py-2">
                                        <div class="flex items-center gap-1">
                                            <button
                                                type="button"
                                                @mousedown.prevent
                                                @click="openInsertMenu()"
                                                class="inline-flex h-7 items-center gap-1 rounded-md px-2 text-xs font-medium text-slate-500 transition hover:bg-slate-100 hover:text-on-surface"
                                                title="Insert block">
                                                <span class="material-symbols-outlined text-[16px]">add</span>
                                                Add
                                            </button>

                                            <button
                                                type="button"
                                                @mousedown.prevent
                                                @click="openImagePicker()"
                                                class="inline-flex h-7 items-center gap-1 rounded-md px-2 text-xs font-medium text-slate-500 transition hover:bg-slate-100 hover:text-on-surface"
                                                title="Upload image">
                                                <span class="material-symbols-outlined text-[16px]">image</span>
                                                Image
                                            </button>

                                            <button
                                                type="button"
                                                @mousedown.prevent
                                                @click="openFilePicker()"
                                                class="inline-flex h-7 items-center gap-1 rounded-md px-2 text-xs font-medium text-slate-500 transition hover:bg-slate-100 hover:text-on-surface"
                                                title="Attach file">
                                                <span class="material-symbols-outlined text-[16px]">attach_file</span>
                                                File
                                            </button>
                                        </div>

                                        <div class="flex items-center gap-2 text-[11px] text-slate-400">
                                            <span x-show="uploading" class="inline-flex items-center gap-1.5">
                                                <span class="h-3 w-3 animate-spin rounded-full border-2 border-slate-200 border-t-primary"></span>
                                                Uploading <span x-text="uploadProgress + '%'"></span>
                                            </span>
                                            <span x-show="!uploading">Paste or drop images/files</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Contextual text formatting menu --}}
                            <div
                                x-cloak
                                x-show="bubble.open"
                                x-transition.opacity.duration.100ms
                                :style="`top:${bubble.top}px; left:${bubble.left}px; transform:translateX(-50%);`"
                                class="absolute z-40 flex items-center gap-0.5 rounded-lg border border-slate-200 bg-white p-1 shadow-xl">

                                <button type="button" @mousedown.prevent.stop @click.stop="toggleBold()" :class="active('bold') ? 'bg-slate-100 text-primary' : 'text-slate-600'" class="rounded p-1.5 hover:bg-slate-100" title="Bold">
                                    <span class="material-symbols-outlined text-[17px]">format_bold</span>
                                </button>
                                <button type="button" @mousedown.prevent.stop @click.stop="toggleItalic()" :class="active('italic') ? 'bg-slate-100 text-primary' : 'text-slate-600'" class="rounded p-1.5 hover:bg-slate-100" title="Italic">
                                    <span class="material-symbols-outlined text-[17px]">format_italic</span>
                                </button>
                                <button type="button" @mousedown.prevent.stop @click.stop="toggleUnderline()" :class="active('underline') ? 'bg-slate-100 text-primary' : 'text-slate-600'" class="rounded p-1.5 hover:bg-slate-100" title="Underline">
                                    <span class="inline-flex h-[17px] min-w-[17px] items-center justify-center text-[13px] font-semibold leading-none underline decoration-[1.5px] underline-offset-[3px]">U</span>
                                </button>
                                <button type="button" @mousedown.prevent.stop @click.stop="toggleStrike()" :class="active('strike') ? 'bg-slate-100 text-primary' : 'text-slate-600'" class="rounded p-1.5 hover:bg-slate-100" title="Strikethrough">
                                    <span class="material-symbols-outlined text-[17px]">strikethrough_s</span>
                                </button>
                                <button type="button" @mousedown.prevent.stop @click.stop="toggleInlineCode()" :class="active('code') ? 'bg-slate-100 text-primary' : 'text-slate-600'" class="rounded p-1.5 hover:bg-slate-100" title="Inline code">
                                    <span class="material-symbols-outlined text-[17px]">code</span>
                                </button>

                                <span class="mx-0.5 h-5 w-px bg-slate-200"></span>

                                <button type="button" @mousedown.prevent.stop @click.stop="toggleMonospace()" :class="activeMonospace() ? 'bg-slate-100 text-primary' : 'text-slate-600'" class="rounded px-2 py-1.5 font-mono text-[11px] hover:bg-slate-100" title="Monospace font">Mono</button>
                                <button type="button" @mousedown.prevent.stop @click.stop="setLink()" :class="active('link') ? 'bg-slate-100 text-primary' : 'text-slate-600'" class="rounded p-1.5 hover:bg-slate-100" title="Link">
                                    <span class="material-symbols-outlined text-[17px]">link</span>
                                </button>
                                <button x-show="active('link')" type="button" @mousedown.prevent.stop @click.stop="unsetLink()" class="rounded p-1.5 text-slate-500 hover:bg-slate-100 hover:text-red-500" title="Remove link">
                                    <span class="material-symbols-outlined text-[17px]">link_off</span>
                                </button>
                            </div>

                            {{-- Link URL bar --}}
                            <div
                                x-cloak
                                x-show="linkBar.open"
                                x-transition.opacity.duration.100ms
                                :style="`top:${linkBar.top}px; left:${linkBar.left}px; width:${linkBar.width}px; transform:translateX(-50%);`"
                                class="absolute z-[45] flex items-center gap-2 rounded-lg border border-slate-200 bg-white p-1.5 shadow-xl">

                                <span class="material-symbols-outlined shrink-0 text-[17px] text-slate-400">link</span>

                                <input x-ref="linkInput" type="text" x-model="linkBar.value"
                                    placeholder="https://example.com"
                                    x-on:keydown.enter.prevent="submitLink()" x-on:keydown.escape.prevent="closeLinkBar(true)"
                                    x-on:mousedown.stop x-on:click.stop
                                    class="w-full min-w-0 rounded-md border border-slate-200 bg-slate-50 px-2 py-1 text-sm text-on-surface outline-none transition focus:border-primary/40" />

                                <button type="button" @mousedown.prevent.stop @click.stop="submitLink()"
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-primary text-white transition-opacity hover:opacity-90"
                                    title="Apply link">
                                    <span class="material-symbols-outlined text-[16px]">check</span>
                                </button>

                                <button type="button" @mousedown.prevent.stop @click.stop="closeLinkBar(true)"
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                                    title="Cancel">
                                    <span class="material-symbols-outlined text-[16px]">close</span>
                                </button>
                            </div>

                            {{-- Slash / insert command menu --}}
                            <div
                                x-cloak
                                x-show="slash.open"
                                x-transition.opacity.duration.100ms
                                x-ref="slashMenu"
                                :style="`top:${slash.top}px; left:${slash.left}px;`"
                                class="absolute z-50 w-72 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl">
                                <div class="border-b border-slate-100 px-3 py-2">
                                    <div class="flex items-center gap-2 text-xs text-slate-400">
                                        <span class="material-symbols-outlined text-[16px]">search</span>
                                        <span x-text="slash.query ? `Commands matching “${slash.query}”` : 'Insert block'"></span>
                                    </div>
                                </div>

                                <div class="max-h-60 overflow-y-auto p-1.5">
                                    <template x-for="(item, index) in filteredSlashItems()" :key="item.action">
                                        <button
                                            type="button"
                                            :data-slash-index="index"
                                            :class="index === slash.selectedIndex ? 'bg-slate-100' : 'hover:bg-slate-50'"
                                            @mousedown.prevent.stop
                                            @mouseenter="slash.selectedIndex = index"
                                            @click.stop="runSlashCommand(item.action)"
                                            class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left transition">
                                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-white text-slate-500">
                                                <span class="material-symbols-outlined text-[16px]" x-text="item.icon"></span>
                                            </span>
                                            <span class="min-w-0 flex-1 truncate text-[13px] font-medium text-on-surface" x-text="item.label"></span>
                                        </button>
                                    </template>

                                    <div x-show="filteredSlashItems().length === 0" class="px-3 py-5 text-center text-xs text-slate-400">
                                        No matching command
                                    </div>
                                </div>
                            </div>

                            <input
                                x-ref="imageInput"
                                type="file"
                                accept="image/jpeg,image/png,image/webp,image/gif"
                                class="hidden"
                                @change="handleImagePicker($event)" />

                            <input
                                x-ref="fileInput"
                                type="file"
                                accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.zip,.json"
                                class="hidden"
                                @change="handleFilePicker($event)" />
                        </div>

                        @error($editorErrorProperty ?? 'formDetails')
                        <p class="text-xs text-red-500">{{ $message }}</p>
                        @enderror

                        @error('editorUpload')
                        <p class="text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
