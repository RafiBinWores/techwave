@props(['number'])

<span class="inline-flex flex-wrap items-center gap-2" x-data="{
    number: @js((string) $number),
    copied: false,
    failed: false,
    async copyNumber() {
        this.failed = false;
        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(this.number);
            } else {
                const field = document.createElement('textarea');
                const previousFocus = document.activeElement;
                field.value = this.number;
                field.setAttribute('readonly', '');
                field.style.position = 'fixed';
                field.style.left = '-9999px';
                document.body.appendChild(field);
                try {
                    field.select();
                    if (!document.execCommand('copy')) throw new Error('Copy failed');
                } finally {
                    field.remove();
                    previousFocus?.focus({ preventScroll: true });
                }
            }
            this.copied = true;
            setTimeout(() => this.copied = false, 2000);
        } catch {
            this.copied = false;
            this.failed = true;
            setTimeout(() => this.failed = false, 2000);
        }
    }
}">
    <span>{{ $number }}</span>
    <button type="button" @click="copyNumber()" :aria-label="copied ? 'bKash number copied' : 'Copy bKash number'"
        title="Copy bKash number"
        class="inline-flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center rounded-lg bg-cyan-300/10 text-cyan-200 transition hover:bg-cyan-300/20 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-300">
        <span class="material-symbols-outlined text-base" aria-hidden="true" x-text="copied ? 'check' : 'content_copy'">content_copy</span>
    </button>
    <span role="status" aria-live="polite" class="text-xs font-medium tracking-normal">
        <span x-show="copied" x-cloak class="text-cyan-200">Copied</span>
        <span x-show="failed" x-cloak class="text-amber-200">Could not copy</span>
    </span>
</span>
