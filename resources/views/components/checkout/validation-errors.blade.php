@if ($errors->any())
    <div role="alert" class="mb-6 rounded-2xl border border-red-300/20 bg-red-400/10 p-4 text-sm text-red-100">
        <div class="flex items-start gap-3">
            <span class="material-symbols-outlined text-red-200" aria-hidden="true">error</span>
            <div>
                <p class="font-semibold">Please fix the following errors:</p>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif
