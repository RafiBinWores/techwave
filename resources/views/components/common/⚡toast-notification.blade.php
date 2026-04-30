<?php

use Livewire\Component;

new class extends Component {
    //
};
?>

<div>
    <div x-data="{
        toasts: [],
    
        add(message, type = 'success') {
            const id = Date.now() + Math.floor(Math.random() * 1000);
    
            this.toasts.push({
                id: id,
                message: message,
                type: type,
            });
    
            setTimeout(() => {
                this.remove(id);
            }, 3500);
        },
    
        remove(id) {
            this.toasts = this.toasts.filter(toast => toast.id !== id);
        }
    }" x-on:toast.window="add($event.detail.message, $event.detail.type)"
        class="fixed right-4 top-4 z-[9999] space-y-3">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-6 opacity-0"
                x-transition:enter-end="translate-x-0 opacity-100" x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-x-0 opacity-100" x-transition:leave-end="translate-x-6 opacity-0"
                class="flex w-[340px] items-start gap-3 rounded-2xl border bg-white p-4 shadow-xl"
                :class="{
                    'border-emerald-200': toast.type === 'success',
                    'border-red-200': toast.type === 'error',
                    'border-blue-200': toast.type === 'info',
                    'border-amber-200': toast.type === 'warning',
                }">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
                    :class="{
                        'bg-emerald-50 text-emerald-600': toast.type === 'success',
                        'bg-red-50 text-red-600': toast.type === 'error',
                        'bg-blue-50 text-blue-600': toast.type === 'info',
                        'bg-amber-50 text-amber-600': toast.type === 'warning',
                    }">
                    <span class="material-symbols-outlined text-[20px]"
                        x-text="
                        toast.type === 'success'
                            ? 'check_circle'
                            : toast.type === 'error'
                                ? 'error'
                                : toast.type === 'warning'
                                    ? 'warning'
                                    : 'info'
                    "></span>
                </div>

                <div class="min-w-0 flex-1">
                    {{-- <p class="text-sm font-semibold"
                        :class="{
                            'text-emerald-700': toast.type === 'success',
                            'text-red-700': toast.type === 'error',
                            'text-blue-700': toast.type === 'info',
                            'text-amber-700': toast.type === 'warning',
                        }"
                        x-text="
                        toast.type === 'success'
                            ? 'Success'
                            : toast.type === 'error'
                                ? 'Error'
                                : toast.type === 'warning'
                                    ? 'Warning'
                                    : 'Info'
                    ">
                    </p> --}}

                    <p class="mt-0.5 text-sm text-slate-600" x-text="toast.message"></p>
                </div>

                <button type="button" @click="remove(toast.id)" class="text-slate-400 transition hover:text-slate-700">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>
        </template>
    </div>

    @if (session('toast'))
        <div x-data x-init="$dispatch('toast', {
            type: @js(session('toast.type')),
            message: @js(session('toast.message'))
        })"></div>
    @endif
</div>
