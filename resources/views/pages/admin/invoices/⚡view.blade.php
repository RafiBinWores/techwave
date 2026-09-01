<?php

use App\Models\Invoice;
use App\Models\InvoiceTemplate;
use App\Models\SiteSetting;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.admin-app')] #[Title('Invoice Details')] class extends Component {
    public Invoice $invoice;

    public string $status = '';

    public function mount(Invoice $invoice): void
    {
        $this->invoice = $invoice->load('items');
        $this->status = $this->invoice->status;
    }

    public function invoiceHtml(): string
    {
        $template = InvoiceTemplate::activeTemplate();
        $settings = SiteSetting::current();

        return view('pdf.admin-invoice', [
            'invoice' => $this->invoice,
            'template' => $template,
            'settings' => $settings,
        ])->render();
    }

    public function updateStatus(): void
    {
        $this->validate([
            'status' => ['required', 'in:draft,sent,paid,partially_paid,overdue,cancelled'],
        ]);

        $this->invoice->update(['status' => $this->status]);

        if ($this->status === 'sent') {
            $this->invoice->update(['sent_at' => $this->invoice->sent_at ?: now()]);
        }

        $this->invoice->refresh();

        $this->dispatch('toast', message: 'Invoice status updated to ' . str_replace('_', ' ', $this->status) . '.', type: 'success');
    }

    public function formatDate($date): string
    {
        if (!$date) {
            return 'N/A';
        }

        return Carbon::parse($date)->format('d M Y');
    }
};
?>

<div class="mx-auto w-full space-y-stack-lg">
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div class="flex items-center gap-4">
            <div>
                <h2 class="text-xl font-semibold text-on-surface md:text-h1 md:font-h1">
                    {{ $invoice->subject ?: 'Service Invoice' }}
                </h2>

                <p class="font-mono text-xs text-slate-400">
                    {{ $invoice->invoice_no }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <span @class([
                'inline-flex items-center rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-wider',
                'bg-slate-100 text-slate-600' => $invoice->status === 'draft',
                'bg-blue-100 text-blue-700' => $invoice->status === 'sent',
                'bg-emerald-100 text-emerald-700' => $invoice->status === 'paid',
                'bg-amber-100 text-amber-700' => $invoice->status === 'partially_paid',
                'bg-red-100 text-red-700' => $invoice->status === 'overdue',
                'bg-slate-200 text-slate-500' => $invoice->status === 'cancelled',
            ])>
                {{ str_replace('_', ' ', ucfirst($invoice->status)) }}
            </span>

            <a href="{{ route('admin.invoices.pdf', $invoice) }}"
                class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-label-md font-label-md text-on-primary transition-all hover:shadow-lg hover:shadow-primary/20">
                <span class="material-symbols-outlined text-lg">picture_as_pdf</span>
                Download PDF
            </a>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <iframe
                srcdoc="{{ $this->invoiceHtml() }}"
                title="Invoice preview"
                class="h-[80vh] w-full bg-white"></iframe>
        </div>

        <div class="space-y-6">
            {{-- Status control --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6">
                <h3 class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                    Invoice status
                </h3>

                <div class="mt-4">
                    <select wire:model.live="status"
                        class="w-full rounded-lg border border-outline-variant bg-white px-4 py-2.5 text-label-md font-label-md text-on-surface focus:border-primary focus:ring-2 focus:ring-primary/10">
                        <option value="draft">Draft</option>
                        <option value="sent">Sent</option>
                        <option value="paid">Paid</option>
                        <option value="partially_paid">Partially Paid</option>
                        <option value="overdue">Overdue</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <button type="button" wire:click="updateStatus"
                    class="mt-3 w-full cursor-pointer rounded-lg bg-primary px-5 py-2.5 text-label-md font-label-md text-on-primary transition-all hover:shadow-lg hover:shadow-primary/20 active:scale-[0.98]">
                    Update Status
                </button>
            </div>

            {{-- Summary --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6">
                <h3 class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                    Invoice total
                </h3>

                <div class="mt-5 space-y-4 text-sm">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <span class="text-secondary">Subtotal</span>
                        <span class="font-semibold text-on-surface">
                            ৳{{ number_format($invoice->subtotal(), 2) }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <span class="text-secondary">Discount</span>
                        <span class="font-semibold text-amber-600">
                            - ৳{{ number_format($invoice->discountAmount(), 2) }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between text-base">
                        <span class="font-bold text-on-surface">Total</span>
                        <span class="font-bold text-primary">
                            ৳{{ number_format($invoice->total(), 2) }}
                        </span>
                    </div>
                </div>

                <div class="mt-6 space-y-3 border-t border-slate-100 pt-5 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-secondary">Issued</span>
                        <span class="font-medium text-on-surface">
                            {{ $this->formatDate($invoice->issue_date) }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-secondary">Due Date</span>
                        <span class="font-medium text-on-surface">
                            {{ $this->formatDate($invoice->due_date) }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-secondary">Sent At</span>
                        <span class="font-medium text-on-surface">
                            {{ $this->formatDate($invoice->sent_at) }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Customer card --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6">
                <h3 class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                    Customer
                </h3>

                <div class="mt-5 space-y-3 text-sm">
                    <div class="border-b border-slate-100 pb-3">
                        <span class="block text-secondary">Name</span>
                        <span class="mt-1 block font-medium text-on-surface">
                            {{ $invoice->customer_name ?: 'N/A' }}
                        </span>
                    </div>

                    <div class="border-b border-slate-100 pb-3">
                        <span class="block text-secondary">Email</span>
                        <span class="mt-1 block font-medium text-on-surface">
                            {{ $invoice->customer_email ?: 'N/A' }}
                        </span>
                    </div>

                    <div class="border-b border-slate-100 pb-3">
                        <span class="block text-secondary">Phone</span>
                        <span class="mt-1 block font-medium text-on-surface">
                            {{ $invoice->customer_phone ?: 'N/A' }}
                        </span>
                    </div>

                    <div>
                        <span class="block text-secondary">Company</span>
                        <span class="mt-1 block font-medium text-on-surface">
                            {{ $invoice->company_name ?: 'N/A' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Item list --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6">
                <h3 class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                    Items ({{ $invoice->items->count() }})
                </h3>

                <div class="mt-4 space-y-3">
                    @foreach ($invoice->items as $item)
                        <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3 last:border-0 last:pb-0">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-on-surface">{{ $item->title }}</p>
                                @if ($item->description)
                                    <p class="mt-0.5 text-xs text-secondary">{{ $item->description }}</p>
                                @endif
                                <p class="mt-1 text-xs text-slate-400">
                                    {{ number_format((float) $item->quantity, 1) }} × ৳{{ number_format((float) $item->unit_price, 2) }}
                                </p>
                            </div>

                            <span class="shrink-0 font-mono text-sm font-semibold text-on-surface">
                                ৳{{ number_format((float) $item->quantity * (float) $item->unit_price, 2) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
