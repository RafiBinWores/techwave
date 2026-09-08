    <script>
        window.projectDetailsEditor = function(wire, initialContent = null, detailsProperty = 'formDetails') {
            // Keep Tiptap outside Alpine's reactive object. Alpine proxies component
            // properties, and a proxied Tiptap Editor can produce mismatched
            // transactions / commands that silently fail.
            let editor = null;

            return {
                wire,
                detailsProperty,
                loading: true,
                uploading: false,
                uploadProgress: 0,
                selectionTick: 0,
                savedSelection: {
                    from: 1,
                    to: 1
                },
                insertMenuRequested: false,
                slashFrom: null,
                bubble: {
                    open: false,
                    top: 0,
                    left: 0
                },
                linkBar: {
                    open: false,
                    top: 0,
                    left: 0,
                    width: 0,
                    value: ''
                },
                slash: {
                    open: false,
                    top: 0,
                    left: 0,
                    query: '',
                    selectedIndex: 0,
                    coords: {
                        top: 0,
                        bottom: 0
                    }
                },
                slashMenuItems: [{
                        label: 'Text',
                        action: 'paragraph',
                        icon: 'notes'
                    },
                    {
                        label: 'Heading 1',
                        action: 'heading1',
                        icon: 'format_h1'
                    },
                    {
                        label: 'Heading 2',
                        action: 'heading2',
                        icon: 'format_h2'
                    },
                    {
                        label: 'Heading 3',
                        action: 'heading3',
                        icon: 'format_h3'
                    },
                    {
                        label: 'Bullet list',
                        action: 'bulletList',
                        icon: 'format_list_bulleted'
                    },
                    {
                        label: 'Numbered list',
                        action: 'orderedList',
                        icon: 'format_list_numbered'
                    },
                    {
                        label: 'Checklist',
                        action: 'taskList',
                        icon: 'check_box'
                    },
                    {
                        label: 'Quote',
                        action: 'blockquote',
                        icon: 'format_quote'
                    },
                    {
                        label: 'Code block',
                        action: 'codeBlock',
                        icon: 'code_blocks'
                    },
                    {
                        label: 'Divider',
                        action: 'horizontalRule',
                        icon: 'horizontal_rule'
                    },
                    {
                        label: 'Image',
                        action: 'image',
                        icon: 'image'
                    },
                    {
                        label: 'File',
                        action: 'file',
                        icon: 'attach_file'
                    },
                    {
                        label: 'Link',
                        action: 'link',
                        icon: 'link'
                    },
                ],

                async init() {
                    try {
                        const version = '3.30.5';
                        const [core, starter, imageModule, taskListModule, taskItemModule, fileHandlerModule, placeholderModule, codeBlockModule, textStyleModule, lowlightModule] = await Promise.all([
                            import(`https://esm.sh/@tiptap/core@${version}`),
                            import(`https://esm.sh/@tiptap/starter-kit@${version}`),
                            import(`https://esm.sh/@tiptap/extension-image@${version}`),
                            import(`https://esm.sh/@tiptap/extension-task-list@${version}`),
                            import(`https://esm.sh/@tiptap/extension-task-item@${version}`),
                            import(`https://esm.sh/@tiptap/extension-file-handler@${version}`),
                            import(`https://esm.sh/@tiptap/extension-placeholder@${version}`),
                            import(`https://esm.sh/@tiptap/extension-code-block-lowlight@${version}`),
                            import(`https://esm.sh/@tiptap/extension-text-style@${version}`),
                            import('https://esm.sh/lowlight@3'),
                        ]);

                        const {
                            Editor,
                            Node,
                            mergeAttributes
                        } = core;
                        const StarterKit = starter.default ?? starter.StarterKit;
                        const Image = imageModule.default ?? imageModule.Image;
                        const TaskList = taskListModule.default ?? taskListModule.TaskList;
                        const TaskItem = taskItemModule.default ?? taskItemModule.TaskItem;
                        const FileHandler = fileHandlerModule.default ?? fileHandlerModule.FileHandler;
                        const Placeholder = placeholderModule.default ?? placeholderModule.Placeholder;
                        const CodeBlockLowlight = codeBlockModule.default ?? codeBlockModule.CodeBlockLowlight;
                        const {
                            TextStyle,
                            FontFamily
                        } = textStyleModule;
                        const lowlight = lowlightModule.createLowlight(lowlightModule.common);
                        const self = this;

                        const FileAttachment = Node.create({
                            name: 'fileAttachment',
                            group: 'block',
                            atom: true,
                            selectable: true,
                            draggable: true,

                            addAttributes() {
                                return {
                                    url: {
                                        default: null
                                    },
                                    name: {
                                        default: 'Attachment'
                                    },
                                    sizeLabel: {
                                        default: ''
                                    },
                                    mime: {
                                        default: ''
                                    },
                                };
                            },

                            parseHTML() {
                                return [{
                                    tag: 'a[data-file-attachment="true"]'
                                }];
                            },

                            renderHTML({
                                HTMLAttributes
                            }) {
                                const title = HTMLAttributes.sizeLabel ?
                                    `📎 ${HTMLAttributes.name} · ${HTMLAttributes.sizeLabel}` :
                                    `📎 ${HTMLAttributes.name}`;

                                return [
                                    'a',
                                    mergeAttributes(HTMLAttributes, {
                                        href: HTMLAttributes.url,
                                        target: '_blank',
                                        rel: 'noopener noreferrer',
                                        'data-file-attachment': 'true',
                                    }),
                                    title,
                                ];
                            },
                        });

                        editor = new Editor({
                            element: this.$refs.editor,
                            content: this.parseInitialContent(initialContent),
                            extensions: [
                                StarterKit.configure({
                                    codeBlock: false,
                                    link: {
                                        openOnClick: false,
                                        autolink: true,
                                        linkOnPaste: true,
                                        defaultProtocol: 'https',
                                    },
                                }),
                                CodeBlockLowlight.configure({
                                    lowlight
                                }),
                                Image.configure({
                                    inline: false,
                                    allowBase64: false,
                                    HTMLAttributes: {
                                        loading: 'lazy'
                                    },
                                    resize: {
                                        enabled: true,
                                        minWidth: 80,
                                        minHeight: 80,
                                        alwaysPreserveAspectRatio: true,
                                    },
                                }),
                                TaskList,
                                TaskItem.configure({
                                    nested: true
                                }),
                                TextStyle,
                                FontFamily.configure({
                                    types: ['textStyle']
                                }),
                                Placeholder.configure({
                                    placeholder: 'Write project brief, requirements, decisions, links, code… Type / for commands',
                                }),
                                FileAttachment,
                                FileHandler.configure({
                                    onDrop(currentEditor, files, pos) {
                                        self.handleDroppedFiles(files, pos);
                                    },
                                    onPaste(currentEditor, files) {
                                        self.handlePastedFiles(files);
                                    },
                                }),
                            ],
                            editorProps: {
                                attributes: {
                                    spellcheck: 'true',
                                    autocomplete: 'off',
                                    autocapitalize: 'sentences',
                                },
                                handleKeyDown: (view, event) => {
                                    if (event.key === 'Escape') {
                                        this.closeSlashMenu();
                                        return false;
                                    }

                                    // A space ends a slash command. Let ProseMirror insert the
                                    // space normally, then close the menu immediately after.
                                    if (this.slash.open && event.key === ' ') {
                                        window.setTimeout(() => this.closeSlashMenu(), 0);
                                        return false;
                                    }

                                    // Keyboard navigation through the slash command menu.
                                    if (this.slash.open && ['ArrowDown', 'ArrowUp', 'Enter'].includes(event.key)) {
                                        const items = this.filteredSlashItems();
                                        if (items.length === 0) {
                                            if (event.key === 'Enter') this.closeSlashMenu();
                                            return false;
                                        }

                                        if (event.key === 'ArrowDown') {
                                            event.preventDefault();
                                            this.moveSlashSelection(1);
                                            return true;
                                        }

                                        if (event.key === 'ArrowUp') {
                                            event.preventDefault();
                                            this.moveSlashSelection(-1);
                                            return true;
                                        }

                                        event.preventDefault();
                                        const item = items[this.slash.selectedIndex % items.length];
                                        if (item) this.runSlashCommand(item.action);
                                        return true;
                                    }

                                    if (event.key === '/' && view.state.selection.empty) {
                                        const {
                                            $from
                                        } = view.state.selection;
                                        const before = $from.parent.textBetween(0, $from.parentOffset, '\n', '\n');
                                        const previous = before.slice(-1);

                                        // Linear-style trigger: slash commands start at the
                                        // beginning of a block or after whitespace.
                                        if ($from.parentOffset === 0 || /\s/.test(previous)) {
                                            window.setTimeout(() => this.openSlashFromCursor(), 0);
                                        }
                                    }

                                    return false;
                                },
                            },
                            onUpdate: ({
                                editor
                            }) => {
                                this.syncContent(editor);
                                this.updateSlashQuery();
                            },
                            onSelectionUpdate: ({
                                editor
                            }) => {
                                this.rememberSelection(editor);
                                this.selectionTick++;
                                this.updateBubble(editor);
                            },
                            onBlur: () => {
                                window.setTimeout(() => {
                                    if (!this.slash.open) this.bubble.open = false;
                                }, 120);
                            },
                        });

                        this.loading = false;
                        this.rememberSelection(editor);
                        this.syncContent(editor);
                    } catch (error) {
                        console.error('Project details editor failed to load:', error);
                        this.loading = false;
                        this.$refs.editor.innerHTML = '<div class="px-5 py-8 text-sm text-red-500">The project editor could not load. Check the browser console and network access to esm.sh.</div>';
                    }
                },

                parseInitialContent(content) {
                    if (!content) {
                        return {
                            type: 'doc',
                            content: [{
                                type: 'paragraph'
                            }]
                        };
                    }

                    if (typeof content === 'object') return content;

                    try {
                        return JSON.parse(content);
                    } catch (error) {
                        return content;
                    }
                },

                syncContent(editor) {
                    if (!editor) return;
                    this.wire.set(this.detailsProperty, JSON.stringify(editor.getJSON()), false);
                },

                active(name, attributes = {}) {
                    this.selectionTick;
                    return !!editor?.isActive(name, attributes);
                },

                activeMonospace() {
                    this.selectionTick;
                    const attrs = editor?.getAttributes('textStyle') ?? {};
                    return (attrs.fontFamily ?? '').toLowerCase().includes('mono');
                },

                rememberSelection(editorInstance = editor) {
                    if (!editorInstance) return;
                    const {
                        from,
                        to
                    } = editorInstance.state.selection;
                    this.savedSelection = {
                        from,
                        to
                    };
                },

                restoreSelection() {
                    if (!editor) return false;

                    const max = editor.state.doc.content.size;
                    const from = Math.max(1, Math.min(this.savedSelection?.from ?? editor.state.selection.from, max));
                    const to = Math.max(from, Math.min(this.savedSelection?.to ?? editor.state.selection.to, max));

                    editor.chain().focus().setTextSelection({
                        from,
                        to
                    }).run();
                    return true;
                },

                runTextCommand(command) {
                    if (!editor || !this.restoreSelection()) return;
                    command(editor.chain().focus()).run();
                    this.rememberSelection();
                    this.selectionTick++;
                    this.updateBubble(editor);
                },

                toggleBold() {
                    this.runTextCommand((chain) => chain.toggleBold());
                },
                toggleItalic() {
                    this.runTextCommand((chain) => chain.toggleItalic());
                },
                toggleUnderline() {
                    this.runTextCommand((chain) => chain.toggleUnderline());
                },
                toggleStrike() {
                    this.runTextCommand((chain) => chain.toggleStrike());
                },
                toggleInlineCode() {
                    this.runTextCommand((chain) => chain.toggleCode());
                },

                toggleMonospace() {
                    if (!editor || !this.restoreSelection()) return;
                    if (this.activeMonospace()) {
                        editor.chain().focus().unsetFontFamily().run();
                    } else {
                        editor.chain().focus().setFontFamily('ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace').run();
                    }
                    this.rememberSelection();
                    this.selectionTick++;
                    this.updateBubble(editor);
                },

                setLink() {
                    if (!editor || !this.restoreSelection()) return;
                    const previous = editor.getAttributes('link').href ?? '';
                    this.linkBar.value = previous || 'https://';
                    this.linkBar.width = 300;
                    this.positionLinkBar();
                    this.bubble.open = false;
                    this.slash.open = false;
                    this.linkBar.open = true;
                    this.$nextTick(() => this.$refs.linkInput?.focus());
                },

                positionLinkBar() {
                    const {
                        from,
                        to
                    } = editor.state.selection;
                    const root = this.$root.getBoundingClientRect();

                    try {
                        const start = editor.view.coordsAtPos(from);
                        const end = editor.view.coordsAtPos(Math.max(from, to));
                        const centerX = ((start.left + end.right) / 2) - root.left;
                        const left = Math.min(
                            Math.max(centerX, this.linkBar.width / 2 + 4),
                            Math.max(this.linkBar.width / 2 + 4, root.width - this.linkBar.width / 2 - 4)
                        );

                        const gap = 8;
                        const above = start.top - root.top - 46;
                        const below = end.bottom - root.top + gap;
                        let top;
                        if (above >= 4) {
                            top = above;
                        } else if (below + 40 <= root.height) {
                            top = below;
                        } else {
                            top = Math.max(4, above);
                        }

                        this.linkBar.top = top;
                        this.linkBar.left = left;
                    } catch (error) {
                        this.linkBar.top = 4;
                        this.linkBar.left = 8;
                    }
                },

                submitLink() {
                    if (!editor) return;
                    const href = this.linkBar.value.trim();
                    const {
                        from,
                        to
                    } = editor.state.selection;

                    if (!href) {
                        editor.chain().focus().extendMarkRange('link').unsetLink().run();
                    } else if (from === to) {
                        editor.chain().focus().insertContent({
                            type: 'text',
                            text: href,
                            marks: [{
                                type: 'link',
                                attrs: {
                                    href
                                }
                            }],
                        }).run();
                    } else {
                        editor.chain().focus().extendMarkRange('link').setLink({
                            href
                        }).run();
                    }

                    this.linkBar.open = false;
                    this.rememberSelection();
                    this.selectionTick++;
                },

                closeLinkBar(restoreFocus = false) {
                    this.linkBar.open = false;
                    if (restoreFocus && editor) editor.chain().focus().run();
                },

                unsetLink() {
                    if (!editor || !this.restoreSelection()) return;
                    editor.chain().focus().extendMarkRange('link').unsetLink().run();
                    this.rememberSelection();
                    this.selectionTick++;
                },

                updateBubble(editor) {
                    const {
                        from,
                        to
                    } = editor.state.selection;
                    if (from === to || !editor.isEditable) {
                        this.bubble.open = false;
                        return;
                    }

                    try {
                        const start = editor.view.coordsAtPos(from);
                        const end = editor.view.coordsAtPos(to);
                        const root = this.$root.getBoundingClientRect();
                        const gap = 8;
                        const menuW = 290;
                        const menuH = 40;
                        const centerX = ((start.left + end.right) / 2) - root.left;
                        const left = Math.min(
                            Math.max(centerX, menuW / 2 + 4),
                            Math.max(menuW / 2 + 4, root.width - menuW / 2 - 4)
                        );

                        const above = start.top - root.top - 46;
                        const below = end.bottom - root.top + gap;
                        let top;

                        if (above >= 4 && above + menuH <= root.height) {
                            top = above;
                        } else if (below + menuH <= root.height) {
                            top = below;
                        } else {
                            top = Math.max(4, Math.min(above, root.height - menuH - 4));
                        }

                        this.bubble = {
                            open: true,
                            top,
                            left
                        };
                    } catch (error) {
                        this.bubble.open = false;
                    }
                },

                openSlashFromCursor() {
                    if (!editor) return;
                    const pos = editor.state.selection.from;
                    this.slashFrom = Math.max(1, pos - 1);
                    this.slash.selectedIndex = 0;
                    this.positionSlashMenu(pos);
                    this.slash.open = true;
                    this.slash.query = '';
                    this.flipSlashMenuIfNeeded();
                },

                openInsertMenu() {
                    if (!editor) return;
                    this.restoreSelection();
                    this.rememberSelection();
                    const pos = editor.state.selection.from;
                    this.slashFrom = null;
                    this.slash.selectedIndex = 0;
                    this.positionSlashMenu(pos, true);
                    this.slash.open = true;
                    this.slash.query = '';
                    this.flipSlashMenuIfNeeded();
                },

                openImagePicker() {
                    if (!editor) return;
                    this.restoreSelection();
                    this.rememberSelection();
                    this.$refs.imageInput?.click();
                },

                openFilePicker() {
                    if (!editor) return;
                    this.restoreSelection();
                    this.rememberSelection();
                    this.$refs.fileInput?.click();
                },

                positionSlashMenu(pos, footer = false) {
                    const root = this.$root.getBoundingClientRect();

                    if (footer) {
                        this.slash.top = Math.max(8, root.height - 330);
                        this.slash.left = 12;
                        this.slash.coords = {
                            top: 0,
                            bottom: 0
                        };
                        return;
                    }

                    try {
                        const coords = editor.view.coordsAtPos(pos);
                        this.slash.top = Math.max(8, coords.bottom - root.top + 6);
                        this.slash.left = Math.max(8, Math.min(coords.left - root.left, root.width - 300));
                        this.slash.coords = {
                            top: coords.top,
                            bottom: coords.bottom
                        };
                    } catch (error) {
                        this.slash.top = 48;
                        this.slash.left = 12;
                    }
                },

                flipSlashMenuIfNeeded() {
                    this.$nextTick(() => {
                        if (!this.slash.open) return;
                        const menu = this.$refs.slashMenu;
                        if (!menu) return;
                        const menuHeight = menu.getBoundingClientRect().height;
                        const root = this.$root.getBoundingClientRect();

                        if (this.slash.top + menuHeight > root.height) {
                            if (this.slash.coords.bottom > 0) {
                                const top = this.slash.coords.top - root.top - menuHeight - 8;
                                this.slash.top = Math.max(8, top);
                            } else {
                                this.slash.top = Math.max(8, root.height - menuHeight - 8);
                            }
                        }
                    });
                },

                updateSlashQuery() {
                    if (!this.slash.open || this.slashFrom === null || !editor) return;

                    const to = editor.state.selection.from;
                    if (to < this.slashFrom) {
                        this.closeSlashMenu();
                        return;
                    }

                    const raw = editor.state.doc.textBetween(this.slashFrom, to, '\n', '\n');
                    if (!raw.startsWith('/')) {
                        this.closeSlashMenu();
                        return;
                    }

                    const query = raw.slice(1);
                    if (/\s/.test(query)) {
                        this.closeSlashMenu();
                        return;
                    }

                    if (query.length > 30) {
                        this.closeSlashMenu();
                        return;
                    }

                    this.slash.query = query.toLowerCase();
                    this.slash.selectedIndex = 0;
                    this.scrollSlashToSelected();
                },

                filteredSlashItems() {
                    const query = this.slash.query.trim().toLowerCase();
                    if (!query) return this.slashMenuItems;

                    return this.slashMenuItems.filter((item) =>
                        item.label.toLowerCase().includes(query)
                    );
                },

                moveSlashSelection(delta) {
                    const items = this.filteredSlashItems();
                    if (!items.length) return;

                    let idx = (this.slash.selectedIndex + delta) % items.length;
                    if (idx < 0) idx += items.length;
                    this.slash.selectedIndex = idx;
                    this.scrollSlashToSelected();
                },

                scrollSlashToSelected() {
                    this.$nextTick(() => {
                        const menu = this.$refs.slashMenu;
                        if (!menu) return;
                        const item = menu.querySelector(`[data-slash-index="${this.slash.selectedIndex}"]`);
                        item?.scrollIntoView({
                            block: 'nearest'
                        });
                    });
                },

                runSlashCommand(action) {
                    if (!editor || !action) return;

                    const commandFrom = this.slashFrom;
                    const commandTo = editor.state.selection.from;
                    const shouldRemoveSlash = commandFrom !== null && commandTo >= commandFrom;

                    // Commit removal of `/query` first. Keeping deletion and the block
                    // conversion in the same chain made every slash-menu block action
                    // share one fragile transaction/selection state.
                    if (shouldRemoveSlash) {
                        editor.commands.deleteRange({
                            from: commandFrom,
                            to: commandTo
                        });
                    }

                    editor.chain().focus().run();
                    this.rememberSelection();
                    this.closeSlashMenu();

                    // Browser UI commands run only after the slash text has been removed
                    // and Tiptap has its cursor/focus back.
                    if (action === 'image') {
                        this.openImagePicker();
                        return;
                    }

                    if (action === 'file') {
                        this.openFilePicker();
                        return;
                    }

                    if (action === 'link') {
                        this.setLink();
                        return;
                    }

                    let executed = false;

                    switch (action) {
                        case 'paragraph':
                            executed = editor.chain().focus().setParagraph().run();
                            break;
                        case 'heading1':
                            executed = editor.chain().focus().setHeading({
                                level: 1
                            }).run();
                            break;
                        case 'heading2':
                            executed = editor.chain().focus().setHeading({
                                level: 2
                            }).run();
                            break;
                        case 'heading3':
                            executed = editor.chain().focus().setHeading({
                                level: 3
                            }).run();
                            break;
                        case 'bulletList':
                            executed = editor.chain().focus().toggleBulletList().run();
                            break;
                        case 'orderedList':
                            executed = editor.chain().focus().toggleOrderedList().run();
                            break;
                        case 'taskList':
                            executed = editor.chain().focus().toggleTaskList().run();
                            break;
                        case 'blockquote':
                            executed = editor.chain().focus().toggleBlockquote().run();
                            break;
                        case 'codeBlock':
                            executed = editor.chain().focus().setCodeBlock().run();
                            break;
                        case 'horizontalRule':
                            executed = editor.chain().focus().setHorizontalRule().run();
                            break;
                        default:
                            console.warn('Unknown project editor command:', action);
                            return;
                    }

                    if (!executed) {
                        console.warn('Project editor command could not be applied:', action);
                    }

                    this.rememberSelection();
                    this.selectionTick++;
                    this.updateBubble(editor);
                    this.syncContent(editor);
                },

                closeSlashMenu() {
                    this.slash.open = false;
                    this.slash.query = '';
                    this.slashFrom = null;
                },

                async handleImagePicker(event) {
                    const file = event.target.files?.[0];
                    event.target.value = '';
                    if (file) await this.insertUploadedFile(file, null, true);
                },

                async handleFilePicker(event) {
                    const file = event.target.files?.[0];
                    event.target.value = '';
                    if (file) await this.insertUploadedFile(file, null, false);
                },

                async handleDroppedFiles(files, pos) {
                    for (const file of files) {
                        await this.insertUploadedFile(file, pos, file.type.startsWith('image/'));
                    }
                },

                async handlePastedFiles(files) {
                    for (const file of files) {
                        await this.insertUploadedFile(file, null, file.type.startsWith('image/'));
                    }
                },

                async insertUploadedFile(file, pos = null, forceImage = false) {
                    if (!editor || !file) return;

                    const maxBytes = 20 * 1024 * 1024;
                    if (file.size > maxBytes) {
                        window.alert('Maximum attachment size is 20 MB.');
                        return;
                    }

                    try {
                        const result = await this.uploadToLivewire(file);
                        const insertAt = typeof pos === 'number' ? pos : editor.state.selection.from;

                        if (forceImage || result.kind === 'image') {
                            editor.chain().focus().insertContentAt(insertAt, {
                                type: 'image',
                                attrs: {
                                    src: result.url,
                                    alt: result.name,
                                    title: result.name,
                                },
                            }).run();
                        } else {
                            editor.chain().focus().insertContentAt(insertAt, {
                                type: 'fileAttachment',
                                attrs: {
                                    url: result.url,
                                    name: result.name,
                                    mime: result.mime,
                                    sizeLabel: this.formatBytes(result.size),
                                },
                            }).run();
                        }
                    } catch (error) {
                        console.error('Project editor upload failed:', error);
                        window.alert(error?.message || 'The file could not be uploaded.');
                    }
                },

                uploadToLivewire(file) {
                    this.uploading = true;
                    this.uploadProgress = 0;

                    return new Promise((resolve, reject) => {
                        this.wire.upload(
                            'editorUpload',
                            file,
                            async () => {
                                    try {
                                        const result = await this.wire.storeEditorUpload();
                                        this.uploading = false;
                                        this.uploadProgress = 100;
                                        resolve(result);
                                    } catch (error) {
                                        this.uploading = false;
                                        reject(error);
                                    }
                                },
                                () => {
                                    this.uploading = false;
                                    reject(new Error('Upload failed.'));
                                },
                                (event) => {
                                    this.uploadProgress = event?.detail?.progress ?? event?.progress ?? 0;
                                }
                        );
                    });
                },

                formatBytes(bytes) {
                    const value = Number(bytes || 0);
                    if (value < 1024) return `${value} B`;
                    if (value < 1024 * 1024) return `${(value / 1024).toFixed(1)} KB`;
                    return `${(value / (1024 * 1024)).toFixed(1)} MB`;
                },
            };
        };
    </script>
