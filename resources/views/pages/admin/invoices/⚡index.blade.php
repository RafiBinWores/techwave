<?php

use App\Mail\AdminInvoiceMail;
use App\Models\Invoice;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.admin-app')] #[Title('Invoices')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $status = 'all';
    public int $perPage = 10;
    public bool $showSendModal = false;
    public ?int $sendInvoiceId = null;
    public string $sendEmail = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function invoices()
    {
        $search = trim($this->search);

        return Invoice::query()
            ->with('items')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_no', 'like', '%' . $search . '%')
                        ->orWhere('customer_name', 'like', '%' . $search . '%')
                        ->orWhere('customer_email', 'like', '%' . $search . '%')
                        ->orWhere('customer_phone', 'like', '%' . $search . '%')
                        ->orWhere('company_name', 'like', '%' . $search . '%')
                        ->orWhere('subject', 'like', '%' . $search . '%');
                });
            })
            ->when($this->status !== 'all', function ($query) {
                $query->where('status', $this->status);
            })
            ->latest()
            ->paginate($this->perPage);
    }

    public function openSendModal(int $invoiceId): void
    {
        $invoice = Invoice::findOrFail($invoiceId);

        $this->sendInvoiceId = $invoice->id;
        $this->sendEmail = $invoice->customer_email ?? '';
        $this->showSendModal = true;
    }

    public function closeSendModal(): void
    {
        $this->showSendModal = false;
        $this->sendInvoiceId = null;
        $this->sendEmail = '';
    }

    public function sendInvoice(): void
    {
        $this->validate([
            'sendInvoiceId' => ['required', 'exists:invoices,id'],
            'sendEmail' => ['required', 'email'],
        ]);

        $invoice = Invoice::with('items')->findOrFail($this->sendInvoiceId);

        $invoice->update([
            'customer_email' => $this->sendEmail,
            'status' => 'sent',
            'sent_at' => $invoice->sent_at ?: now(),
        ]);

        $invoice->load('items');

        Mail::to($this->sendEmail)->send(new AdminInvoiceMail($invoice));

        $this->closeSendModal();

        $this->dispatch('toast', message: 'Invoice sent to ' . $this->sendEmail . ' successfully.', type: 'success');
    }

    public function markAsSent(int $invoiceId): void
    {
        $invoice = Invoice::findOrFail($invoiceId);

        $invoice->update([
            'status' => 'sent',
            'sent_at' => $invoice->sent_at ?: now(),
        ]);

        $this->dispatch('toast', message: 'Invoice marked as sent.', type: 'success');
    }

    public function delete(int $invoiceId): void
    {
        Invoice::findOrFail($invoiceId)->delete();

        $this->dispatch('toast', message: 'Invoice deleted successfully.', type: 'success');
    }
};
?>

<div x-data="{ downloading: false }">
    <div x-cloak x-show="downloading" x-transition.opacity.duration.200ms @click="downloading = false"
        class="fixed inset-0 z-50 flex flex-col items-center justify-center gap-4 bg-slate-900/50 backdrop-blur-sm">
        <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white shadow-xl">
            <svg class="h-8 w-8 animate-spin text-primary" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
            </svg>
        </div>

        <p class="text-sm font-semibold text-white">Preparing your PDF…</p>
    </div>

    <div class="mx-auto w-full space-y-stack-lg">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <h2 class="text-xl font-semibold text-on-surface md:text-h1 md:font-h1">
                    Invoices
                </h2>

                <p class="text-xs font-body-md text-secondary md:text-body-md">
                    Create and send customer invoices with discounts, custom services, and selected plans.
                </p>
            </div>

            <div class="flex w-full flex-col gap-4 lg:w-auto lg:flex-row lg:items-center">
                <div class="grid w-full grid-cols-1 gap-3 sm:grid-cols-2 lg:max-w-xl">
                    <div class="relative">
                        <span
                            class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                            search
                        </span>

                        <input type="search" wire:model.live.debounce.400ms="search" placeholder="Search invoice..."
                            class="w-full rounded-lg border border-outline-variant bg-white py-2.5 pl-10 pr-4 text-label-md font-label-md text-on-surface transition-colors placeholder:text-secondary focus:border-primary focus:ring-2 focus:ring-primary/10" />
                    </div>

                    <div class="relative">
                        <select wire:model.live="status"
                            class="w-full appearance-none rounded-lg border border-outline-variant bg-white px-4 py-2.5 pr-10 text-label-md font-label-md text-on-surface focus:border-primary focus:ring-2 focus:ring-primary/10">
                            <option value="all">All Status</option>
                            <option value="draft">Draft</option>
                            <option value="sent">Sent</option>
                            <option value="paid">Paid</option>
                            <option value="partially_paid">Partially Paid</option>
                            <option value="overdue">Overdue</option>
                            <option value="cancelled">Cancelled</option>
                        </select>

                        <span
                            class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                            expand_more
                        </span>
                    </div>
                </div>

                <a href="{{ route('admin.invoices.create') }}" wire:navigate
                    class="flex w-full shrink-0 items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-label-md font-label-md text-on-primary transition-all hover:shadow-lg hover:shadow-primary/20 active:scale-[0.98] sm:w-auto">
                    <span class="material-symbols-outlined text-lg">add</span>
                    Create New
                </a>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/50">
                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Invoice</th>
                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Customer</th>
                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Items</th>
                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Total</th>
                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Status</th>
                            <th
                                class="px-6 py-4 text-right text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($this->invoices() as $invoice)
                            <tr wire:key="invoice-{{ $invoice->id }}" class="transition-colors hover:bg-slate-50/80">
                                <td class="px-6 py-4">
                                    <div>
                                        <span class="block text-label-md font-label-md text-on-surface">
                                            {{ $invoice->subject }}
                                        </span>

                                        <span class="block font-mono text-[11px] text-slate-400">
                                            {{ $invoice->invoice_no }}
                                        </span>

                                        @if ($invoice->due_date)
                                            <span class="mt-1 block text-xs text-secondary">
                                                Due {{ $invoice->due_date->format('M d, Y') }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <span class="block text-body-sm text-on-surface">
                                        {{ $invoice->customer_name }}
                                    </span>

                                    @if ($invoice->company_name)
                                        <span class="block text-xs text-secondary">
                                            {{ $invoice->company_name }}
                                        </span>
                                    @endif

                                    @if ($invoice->customer_email)
                                        <span class="block text-xs text-slate-400">
                                            {{ $invoice->customer_email }}
                                        </span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 font-mono text-body-sm text-secondary">
                                    {{ $invoice->items->count() }}
                                </td>

                                <td class="px-6 py-4 font-mono text-body-sm text-on-surface">
                                    ৳{{ number_format($invoice->total(), 2) }}
                                </td>

                                <td class="px-6 py-4">
                                    <span @class([
                                        'inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider',
                                        'bg-slate-100 text-slate-600' => $invoice->status === 'draft',
                                        'bg-blue-100 text-blue-700' => $invoice->status === 'sent',
                                        'bg-emerald-100 text-emerald-700' => $invoice->status === 'paid',
                                        'bg-amber-100 text-amber-700' => $invoice->status === 'partially_paid',
                                        'bg-red-100 text-red-700' => $invoice->status === 'overdue',
                                        'bg-slate-200 text-slate-500' => $invoice->status === 'cancelled',
                                    ])>
                                        {{ str_replace('_', ' ', ucfirst($invoice->status)) }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <div x-data="{ open: false }" class="relative inline-block text-left">
                                        <button type="button" @click="open = !open"
                                            class="text-slate-400 transition-colors hover:text-primary">
                                            <span class="material-symbols-outlined">more_vert</span>
                                        </button>

                                        <div x-cloak x-show="open" @click.outside="open = false" x-transition
                                            class="absolute right-0 z-20 mt-2 w-48 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                                            <a href="{{ route('admin.invoices.view', $invoice) }}" wire:navigate
                                                class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50">
                                                <span class="material-symbols-outlined text-[18px]">visibility</span>
                                                View
                                            </a>

                                            <a href="{{ route('admin.invoices.edit', $invoice) }}" wire:navigate
                                                class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50">
                                                <span class="material-symbols-outlined text-[18px]">edit</span>
                                                Edit
                                            </a>

                                            <a href="{{ route('admin.invoices.pdf', $invoice) }}"
                                                @click="downloading = true; setTimeout(() => downloading = false, 8000)"
                                                class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50">
                                                <span class="material-symbols-outlined text-[18px]">picture_as_pdf</span>
                                                Download PDF
                                            </a>

                                            <button type="button" wire:click="openSendModal({{ $invoice->id }})"
                                                class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50 cursor-pointer">
                                                <span class="material-symbols-outlined text-[18px]">send</span>
                                                Send to Customer
                                            </button>

                                            @if ($invoice->status === 'draft')
                                                <button type="button" wire:click="markAsSent({{ $invoice->id }})"
                                                    class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50 cursor-pointer">
                                                    <span class="material-symbols-outlined text-[18px]">mail</span>
                                                    Mark Sent
                                                </button>
                                            @endif

                                            <button type="button" wire:click="delete({{ $invoice->id }})"
                                                wire:confirm="Are you sure you want to delete this invoice?"
                                                class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50">
                                                <span class="material-symbols-outlined text-[18px]">delete</span>
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-14 text-center">
                                    <div class="mx-auto flex max-w-sm flex-col items-center">
                                        <div
                                            class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                            <span class="material-symbols-outlined">receipt_long</span>
                                        </div>

                                        <h3 class="text-base font-semibold text-on-surface">
                                            No invoices found
                                        </h3>

                                        <p class="mt-1 text-sm text-secondary">
                                            Create your first customer invoice.
                                        </p>

                                        <a href="{{ route('admin.invoices.create') }}" wire:navigate
                                            class="mt-5 rounded-lg bg-primary px-5 py-2.5 text-sm font-medium text-white transition hover:opacity-90">
                                            Create Invoice
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div
                class="flex flex-col gap-4 border-t border-slate-100 bg-slate-50/30 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-body-sm font-body-sm text-secondary">Per page</span>

                    <select wire:model.live="perPage"
                        class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-600 focus:border-primary focus:ring-primary/10">
                        <option value="10">10</option>
                        <option value="15">15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>

                <div>
                    {{ $this->invoices()->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- Send to customer modal --}}
    @if ($showSendModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-xl">
                <div class="mb-5 flex items-start justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-on-surface">Send Invoice</h3>
                        <p class="mt-1 text-sm text-secondary">Send this invoice to your customer as an email with the
                            PDF attached.</p>
                    </div>

                    <button type="button" wire:click="closeSendModal"
                        class="text-slate-400 transition hover:text-slate-600">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <div class="space-y-2">
                    <label class="block font-label-md text-on-surface">Customer Email</label>
                    <input type="email" wire:model="sendEmail"
                        class="w-full rounded border border-outline-variant px-4 py-2.5" placeholder="customer@email.com" />

                    @error('sendEmail')
                        <p class="text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" wire:click="closeSendModal"
                        class="rounded-lg border border-outline-variant px-5 py-2 text-label-md font-label-md text-on-surface transition-colors hover:bg-slate-50 cursor-pointer">
                        Cancel
                    </button>

                    <button type="button" wire:click="sendInvoice" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-2 text-label-md font-label-md text-white shadow-sm transition-opacity hover:opacity-90 cursor-pointer">
                        <span wire:loading.remove wire:target="sendInvoice">Send Invoice</span>

                        <span wire:loading wire:target="sendInvoice" class="inline-flex items-center gap-2">
                            <span
                                class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                            Sending...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
