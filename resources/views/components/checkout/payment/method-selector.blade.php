@props(['onlineAvailable', 'manualAvailable'])

<fieldset class="mb-4 grid gap-3 sm:grid-cols-2">
    <legend class="mb-2 text-base font-semibold text-white">Payment method</legend>
    @if ($onlineAvailable)
        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-cyan-300/30 bg-cyan-300/5 p-3">
            <input type="radio" name="payment_method" value="sslcommerz" wire:model.live="paymentMethod" class="mt-1 accent-cyan-300">
            <span><span class="block font-semibold text-white">SSLCommerz</span><span class="mt-1 block text-xs text-blue-100/60">Cards, mobile banking and online payments</span></span>
        </label>
    @endif
    @if ($manualAvailable)
        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-blue-100/20 bg-white/5 p-3">
            <input type="radio" name="payment_method" value="manual" wire:model.live="paymentMethod" class="mt-1 accent-cyan-300">
            <span><span class="block font-semibold text-white">Manual bKash</span><span class="mt-1 block text-xs text-blue-100/60">Send money and submit your transaction details</span></span>
        </label>
    @endif
</fieldset>
