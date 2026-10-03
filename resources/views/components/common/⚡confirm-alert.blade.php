<?php

use Livewire\Component;

new class extends Component {
    //
};
?>

<div>
    <div x-data="{
        open: false,
        title: '',
        message: '',
        confirmText: 'Confirm',
        cancelText: 'Cancel',
        danger: true,
        showCancel: true,
        resolver: null,

        init() {
            window.confirmAlert = (options = {}) => new Promise((resolve) => {
                window.dispatchEvent(new CustomEvent('confirm-alert', {
                    detail: { ...options, resolve },
                }));
            });
        },

        ask(detail) {
            this.title = detail.title ?? 'Are you sure?';
            this.message = detail.message ?? '';
            this.confirmText = detail.confirmText ?? 'Confirm';
            this.cancelText = detail.cancelText ?? 'Cancel';
            this.danger = detail.danger ?? true;
            this.showCancel = detail.showCancel ?? true;
            this.resolver = detail.resolve;
            this.open = true;
        },

        settle(value) {
            if (! this.open) {
                return;
            }

            this.open = false;

            const resolver = this.resolver;
            this.resolver = null;

            if (resolver) {
                resolver(value);
            }
        },

        confirm() {
            this.settle(true);
        },

        cancel() {
            this.settle(false);
        },
    }" x-on:confirm-alert.window="ask($event.detail)" x-on:keydown.escape.window="cancel()"
        x-show="open" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95" style="display: none;"
        class="fixed inset-0 z-[9999] flex items-center justify-center p-4" role="alertdialog"
        aria-modal="true">

        <div class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm" x-on:click="cancel()"></div>

        <div class="relative w-full max-w-md rounded-2xl border border-white/10 bg-slate-900 p-6 shadow-2xl">
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border"
                    :class="danger
                        ? 'border-red-300/25 bg-red-400/10 text-red-300'
                        : 'border-amber-300/25 bg-amber-400/10 text-amber-300'">
                    <span class="material-symbols-outlined text-[22px]"
                        x-text="danger ? 'delete' : 'help'"></span>
                </div>

                <div class="min-w-0 flex-1">
                    <h3 class="text-base font-extrabold text-white" x-text="title"></h3>
                    <p class="mt-1.5 text-sm leading-6 text-blue-100/70" x-text="message"></p>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" x-show="showCancel" x-on:click="cancel()"
                    class="cursor-pointer rounded-xl border border-white/15 bg-white/8 px-4 py-2 text-xs font-bold uppercase tracking-wider text-white transition hover:bg-white/12">
                    <span x-text="cancelText"></span>
                </button>

                <button type="button" x-on:click="confirm()"
                    class="cursor-pointer rounded-xl px-4 py-2 text-xs font-bold uppercase tracking-wider transition"
                    :class="danger
                        ? 'border border-red-300/30 bg-red-500 text-white hover:bg-red-400'
                        : 'bg-primary text-white hover:opacity-90'">
                    <span x-text="confirmText"></span>
                </button>
            </div>
        </div>
    </div>
</div>
