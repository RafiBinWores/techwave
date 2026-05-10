<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div x-data="{
        show: false,
        message: '',
        type: 'success',
        timeout: null,
    
        openToast(event) {
            this.message = event.detail.message || 'Success';
            this.type = event.detail.type || 'success';
            this.show = true;
    
            clearTimeout(this.timeout);
    
            this.timeout = setTimeout(() => {
                this.show = false;
            }, 3500);
        }
    }" x-on:toast.window="openToast($event)" class="fixed right-5 top-5 z-[9999]">
        <div x-show="show" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-2 scale-95"
            class="min-w-[280px] max-w-sm rounded-2xl border border-white/10 bg-slate-950/90 p-4 text-white shadow-2xl shadow-black/30 backdrop-blur-xl"
            style="display: none;">
            <div class="flex items-start gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
                    :class="{
                        'bg-emerald-500/15 text-emerald-300': type === 'success',
                        'bg-red-500/15 text-red-300': type === 'error',
                        'bg-blue-500/15 text-blue-300': type === 'info',
                        'bg-amber-500/15 text-amber-300': type === 'warning'
                    }">
                    <span class="material-symbols-outlined text-[20px]"
                        x-text="
                    type === 'success' ? 'check_circle' :
                    type === 'error' ? 'error' :
                    type === 'warning' ? 'warning' :
                    'info'
                "></span>
                </div>

                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold"
                        x-text="
                    type === 'success' ? 'Success' :
                    type === 'error' ? 'Error' :
                    type === 'warning' ? 'Warning' :
                    'Notice'
                ">
                    </p>

                    <p class="mt-1 text-sm leading-6 text-blue-100/75" x-text="message"></p>
                </div>

                <button type="button" @click="show = false"
                    class="rounded-full p-1 text-blue-100/50 transition hover:bg-white/10 hover:text-white">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>
        </div>
    </div>