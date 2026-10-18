@props(['number', 'amount', 'instructions' => null, 'decimals' => 2, 'live' => false])

<div class="mt-4 overflow-hidden rounded-xl border border-cyan-400/15 bg-cyan-400/5">
    <div class="bg-cyan-400/10 px-5 py-3">
        <p class="text-xs font-bold uppercase tracking-wider text-cyan-300">Send Money
            To
        </p>
    </div>
    <div class="p-5">
        <p class="text-xl font-bold tracking-wide text-white">
            <x-checkout.payment.bkash-number :number="$number" />
        </p>
        <p class="mt-1 text-sm text-blue-100/50">bKash Merchant Number</p>
        <div class="mt-4 flex items-baseline gap-1">
            <span
                class="text-2xl font-extrabold text-white">৳{{ number_format($amount, $decimals) }}</span>
            <span class="text-sm text-blue-100/45">BDT</span>
        </div>
    </div>
</div>

@if ($instructions)
<div x-data="{ show: false }"
    class="mt-4 overflow-hidden rounded-xl border border-white/10 bg-black/10">
    <button type="button" @click="show = !show"
        class="flex w-full items-center gap-2 px-4 py-3 text-left transition hover:bg-white/5 cursor-pointer">
        <span class="material-symbols-outlined text-base text-cyan-300">info</span>
        <span
            class="text-xs font-semibold uppercase tracking-wider text-cyan-300/70">How
            to
            pay</span>
        <span class="ml-auto text-blue-100/40 transition"
            :class="{ 'rotate-180': show }">
            <span class="material-symbols-outlined text-base">expand_more</span>
        </span>
    </button>
    <div x-show="show" x-transition
        class="border-t border-white/10 px-4 py-3 space-y-1">
        @foreach (explode("\n", $instructions) as $line)
        @if (trim($line))
        <p class="text-xs text-blue-100/60">{{ $line }}</p>
        @endif
        @endforeach
    </div>
</div>
@endif

<div class="mt-4 space-y-4">
    <div>
        <label class="mb-1.5 block text-sm font-semibold text-blue-100/80">Your bKash Number</label>
        <input type="text" wire:model{{ $live ? '.live' : '' }}="sender_bkash"
            class="w-full rounded-xl border border-white/15 bg-white/8 px-4 py-2.5 text-white placeholder-blue-100/30 outline-none transition focus:border-cyan-400/50 focus:ring-2 focus:ring-cyan-400/10"
            placeholder="01XXXXXXXXX" />
        @error('sender_bkash')
        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="mb-1.5 block text-sm font-semibold text-blue-100/80">bKash Transaction ID
            (TrxID)</label>
        <input type="text" wire:model{{ $live ? '.live' : '' }}="transaction_id"
            class="w-full rounded-xl border border-white/15 bg-white/8 px-4 py-2.5 text-white placeholder-blue-100/30 outline-none transition focus:border-cyan-400/50 focus:ring-2 focus:ring-cyan-400/10"
            placeholder="Enter the TrxID from your bKash app" />
        @error('transaction_id')
        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>
</div>
