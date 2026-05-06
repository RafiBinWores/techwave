<?php

use App\Models\InvoiceTemplate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.admin-app')] #[Title('Invoice Template')] class extends Component {
    public InvoiceTemplate $template;

    public string $name = '';
    public string $subject_prefix = '';
    public string $title = '';
    public string $greeting = '';
    public string $intro_text = '';
    public string $footer_text = '';
    public string $terms_text = '';
    public string $brand_color = '#0F52BA';

    public function mount(): void
    {
        $this->template = InvoiceTemplate::activeTemplate();

        $this->name = $this->template->name;
        $this->subject_prefix = $this->template->subject_prefix;
        $this->title = $this->template->title;
        $this->greeting = $this->template->greeting;
        $this->intro_text = $this->template->intro_text ?? '';
        $this->footer_text = $this->template->footer_text ?? '';
        $this->terms_text = $this->template->terms_text ?? '';
        $this->brand_color = $this->template->brand_color;
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'subject_prefix' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'greeting' => ['required', 'string', 'max:255'],
            'intro_text' => ['nullable', 'string'],
            'footer_text' => ['nullable', 'string'],
            'terms_text' => ['nullable', 'string'],
            'brand_color' => ['required', 'string', 'max:20'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        InvoiceTemplate::query()->update(['is_active' => false]);

        $this->template->update([
            'name' => $validated['name'],
            'subject_prefix' => $validated['subject_prefix'],
            'title' => $validated['title'],
            'greeting' => $validated['greeting'],
            'intro_text' => $validated['intro_text'] ?: null,
            'footer_text' => $validated['footer_text'] ?: null,
            'terms_text' => $validated['terms_text'] ?: null,
            'brand_color' => $validated['brand_color'],
            'is_active' => true,
        ]);

        $this->template = $this->template->fresh();

        $this->dispatch('toast', message: 'Invoice template updated successfully.', type: 'success');
    }
};
?>

<div>
    <div class="mx-auto w-full max-w-7xl space-y-8">
        <div>
            <h1 class="text-h1 font-h1 text-on-surface">Invoice Template</h1>
            <p class="mt-1 text-body-md text-secondary">
                Customize the invoice/proposal email design and content. Future proposal emails will use this template.
            </p>
        </div>

        <form wire:submit.prevent="save">
            <div class="grid grid-cols-12 gap-6">
                <div class="col-span-12 space-y-6 lg:col-span-6">
                    <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                        <h3 class="mb-8 flex items-center gap-2 text-h3 font-h2">
                            <span class="material-symbols-outlined text-primary">receipt_long</span>
                            Template Content
                        </h3>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div class="space-y-2">
                                <label class="block font-label-md text-on-surface">Template Name</label>
                                <input type="text" wire:model.live="name"
                                    class="w-full rounded border border-outline-variant px-4 py-2.5" />
                                @error('name')
                                    <p class="text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-2">
                                <label class="block font-label-md text-on-surface">Subject Prefix</label>
                                <input type="text" wire:model.live="subject_prefix"
                                    class="w-full rounded border border-outline-variant px-4 py-2.5" />
                                @error('subject_prefix')
                                    <p class="text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-2 md:col-span-2">
                                <label class="block font-label-md text-on-surface">Title</label>
                                <input type="text" wire:model.live="title"
                                    class="w-full rounded border border-outline-variant px-4 py-2.5" />
                                @error('title')
                                    <p class="text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-2 md:col-span-2">
                                <label class="block font-label-md text-on-surface">Greeting</label>
                                <input type="text" wire:model.live="greeting"
                                    class="w-full rounded border border-outline-variant px-4 py-2.5" />
                                @error('greeting')
                                    <p class="text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-2 md:col-span-2">
                                <label class="block font-label-md text-on-surface">Intro Text</label>
                                <textarea wire:model.live="intro_text" rows="4" class="w-full rounded border border-outline-variant px-4 py-2.5"></textarea>
                                @error('intro_text')
                                    <p class="text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-2 md:col-span-2">
                                <label class="block font-label-md text-on-surface">Terms Text</label>
                                <textarea wire:model.live="terms_text" rows="4" class="w-full rounded border border-outline-variant px-4 py-2.5"></textarea>
                                @error('terms_text')
                                    <p class="text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-2 md:col-span-2">
                                <label class="block font-label-md text-on-surface">Footer Text</label>
                                <textarea wire:model.live="footer_text" rows="3" class="w-full rounded border border-outline-variant px-4 py-2.5"></textarea>
                                @error('footer_text')
                                    <p class="text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-2">
                                <label class="block font-label-md text-on-surface">Brand Color</label>
                                <div class="flex gap-3">
                                    <input type="color" wire:model.live="brand_color"
                                        class="h-11 w-16 rounded border border-outline-variant" />
                                    <input type="text" wire:model.live="brand_color"
                                        class="w-full rounded border border-outline-variant px-4 py-2.5" />
                                </div>
                                @error('brand_color')
                                    <p class="text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex justify-end">
                            <button type="submit" wire:loading.attr="disabled"
                                class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-label-md font-label-md text-white shadow-sm transition-opacity hover:opacity-90">
                                <span wire:loading.remove wire:target="save">Save Template</span>
                                <span wire:loading wire:target="save" class="inline-flex items-center gap-2">
                                    <span
                                        class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                    Saving...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-span-12 lg:col-span-6">
                    <div class="sticky top-20 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="mb-5 text-h3 font-h2">Live Preview</h3>

                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                                {{-- Header --}}
                                <div class="p-6">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="w-[55%]">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="flex h-12 w-12 items-center justify-center overflow-hidden rounded bg-blue-50 ring-1 ring-blue-100">
                                                    <span class="text-[10px] font-semibold text-slate-400">Logo</span>
                                                </div>

                                                <div>
                                                    <h1
                                                        class="text-2xl font-bold uppercase tracking-tight text-slate-900">
                                                        TechWave
                                                    </h1>
                                                    <p
                                                        class="mt-1 text-[10px] uppercase tracking-[0.18em] text-slate-500">
                                                        {{ $title ?: 'Invoice' }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="mt-4 space-y-1 text-xs leading-relaxed text-slate-500">
                                                <p>128 Innovation Way</p>
                                                <p>Tech District, Dhaka</p>
                                                <p>contact@techwave.io</p>
                                                <p>+880 1XXX XXXXXX</p>
                                            </div>
                                        </div>

                                        <div class="w-[45%] text-right">
                                            <h2 class="mb-4 text-3xl font-extrabold"
                                                style="color: {{ $brand_color }}">
                                                PROPOSAL
                                            </h2>

                                            <div class="grid grid-cols-2 gap-x-6 gap-y-2 text-right text-xs">
                                                <span class="uppercase tracking-wider text-slate-400">Proposal #</span>
                                                <span class="font-mono font-bold text-slate-900">PROP-2026-0892</span>

                                                <span class="uppercase tracking-wider text-slate-400">Date
                                                    Issued</span>
                                                <span class="text-slate-600">May 05, 2026</span>

                                                <span class="uppercase tracking-wider text-slate-400">Due Date</span>
                                                <span class="font-bold" style="color: {{ $brand_color }}">May 12,
                                                    2026</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Bill To / Details --}}
                                <div class="px-6 pb-2">
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                                            <p
                                                class="mb-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                                Customer Details
                                            </p>

                                            <div class="space-y-1 text-xs leading-relaxed text-slate-600">
                                                <p><strong class="text-slate-900">Mr. Customer Name</strong></p>
                                                <p>customer@email.com</p>
                                                <p>+880 1XXX XXXXXX</p>
                                                <p class="font-semibold" style="color: {{ $brand_color }}">Customer
                                                    Company</p>
                                            </div>
                                        </div>

                                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                                            <p
                                                class="mb-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                                Proposal Details
                                            </p>

                                            <div class="space-y-1 text-xs leading-relaxed text-slate-600">
                                                <p><strong>Subject:</strong> Managed IT Service Proposal</p>
                                                <p><strong>Status:</strong> Sent</p>
                                                <div class="mt-2 rounded-lg border-l-4 bg-slate-50 p-2"
                                                    style="border-color: {{ $brand_color }}">
                                                    <p class="text-[11px] text-slate-500">
                                                        <strong>Note:</strong> Special discount valid for 7 days.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Items --}}
                                <div class="px-6 pt-4">
                                    <div class="overflow-hidden rounded-lg border border-slate-200">
                                        <table class="w-full border-collapse text-left text-xs">
                                            <thead>
                                                <tr class="text-white" style="background: {{ $brand_color }}">
                                                    <th class="px-4 py-3 uppercase tracking-wider">Service</th>
                                                    <th class="px-4 py-3 uppercase tracking-wider">Description</th>
                                                    <th class="px-4 py-3 text-center uppercase tracking-wider">Qty</th>
                                                    <th class="px-4 py-3 text-right uppercase tracking-wider">Unit
                                                        Price</th>
                                                    <th class="px-4 py-3 text-right uppercase tracking-wider">Total
                                                    </th>
                                                </tr>
                                            </thead>

                                            <tbody class="divide-y divide-slate-200">
                                                <tr class="bg-white">
                                                    <td class="px-4 py-4 align-top font-bold text-slate-900">Network
                                                        Audit</td>
                                                    <td class="px-4 py-4 align-top text-[11px] text-slate-500">
                                                        Comprehensive security and performance audit.
                                                    </td>
                                                    <td class="px-4 py-4 text-center align-top text-slate-600">1.0</td>
                                                    <td class="px-4 py-4 text-right align-top text-slate-600">৳2,450.00
                                                    </td>
                                                    <td
                                                        class="px-4 py-4 text-right align-top font-bold text-slate-900">
                                                        ৳2,450.00</td>
                                                </tr>

                                                <tr class="bg-slate-50">
                                                    <td class="px-4 py-4 align-top font-bold text-slate-900">Cloud
                                                        Migration</td>
                                                    <td class="px-4 py-4 align-top text-[11px] text-slate-500">
                                                        Migration of legacy system to cloud.
                                                    </td>
                                                    <td class="px-4 py-4 text-center align-top text-slate-600">2.0</td>
                                                    <td class="px-4 py-4 text-right align-top text-slate-600">
                                                        ৳12,000.00</td>
                                                    <td
                                                        class="px-4 py-4 text-right align-top font-bold text-slate-900">
                                                        ৳24,000.00</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                {{-- Summary --}}
                                <div class="px-6 pt-6">
                                    <div class="flex justify-end">
                                        <div class="w-full max-w-sm">
                                            <div class="space-y-3">
                                                <div class="flex justify-between text-sm text-slate-500">
                                                    <span>Subtotal</span>
                                                    <span class="font-mono">৳26,450.00</span>
                                                </div>

                                                <div class="flex justify-between text-sm text-slate-500">
                                                    <span>Discount</span>
                                                    <span class="font-mono text-red-600">-৳1,500.00</span>
                                                </div>

                                                <div class="flex items-center justify-between border-t-2 pt-3"
                                                    style="border-color: {{ $brand_color }}">
                                                    <span class="text-xl font-bold uppercase"
                                                        style="color: {{ $brand_color }}">
                                                        Total Amount
                                                    </span>
                                                    <span class="text-2xl font-extrabold"
                                                        style="color: {{ $brand_color }}">
                                                        ৳24,950.00
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Footer --}}
                                <div class="px-6 py-8">
                                    <div class="grid grid-cols-2 gap-8 border-t border-slate-200 pt-6">
                                        <div>
                                            <h4
                                                class="mb-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                                Terms & Conditions
                                            </h4>
                                            <p class="text-xs leading-relaxed text-slate-500">
                                                {{ $terms_text ?: 'Net terms apply. Please contact support for invoice related queries.' }}
                                            </p>
                                        </div>

                                        <div class="flex flex-col items-end justify-end text-right">
                                            <p class="mb-1 text-xl font-bold" style="color: {{ $brand_color }}">
                                                Thank you for your business!
                                            </p>
                                            <p class="text-xs text-slate-400">
                                                {{ $footer_text ?: 'Empowering your digital infrastructure.' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <p class="mt-3 text-center text-[11px] text-slate-400">
                                This preview now matches your invoice mail structure.
                            </p>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
