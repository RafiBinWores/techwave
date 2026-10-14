@props(['label', 'selected' => false, 'preview', 'property' => null, 'value' => null])

<button type="button" wire:ignore.self data-active="{{ $selected ? 'yes' : 'no' }}"
    aria-pressed="{{ $selected ? 'true' : 'false' }}"
    @if ($property)
    :data-active="choices[@js($property)] === @js($value) ? 'yes' : 'no'"
    :aria-pressed="choices[@js($property)] === @js($value) ? 'true' : 'false'"
    @click="choose(@js($property), @js($value))"
    @endif
    {{ $attributes->class('qr-design-option relative flex min-w-0 cursor-pointer flex-col items-center gap-2 rounded-xl px-3 py-3 text-xs font-medium transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-300') }}>
    <span class="flex h-20 w-full items-center justify-center rounded-lg bg-white p-1.5" aria-hidden="true">
        <img src="{{ $preview }}" alt="" class="h-full max-w-full rounded-md object-contain">
    </span>
    <span class="leading-4">{{ $label }}</span>
    <span class="material-symbols-outlined absolute right-1.5 top-1.5 text-sm text-cyan-200" aria-hidden="true"
        @if ($property) x-show="choices[@js($property)] === @js($value)" x-cloak
        @elseif (!$selected) style="display:none" @endif>check_circle</span>
</button>
