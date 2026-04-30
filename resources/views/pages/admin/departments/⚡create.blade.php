<?php

use App\Models\Department;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.admin-app')] #[Title('Create Department')] class extends Component {
    public string $name = '';
    public bool $is_active = true;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120', Rule::unique('departments', 'name')],
            'is_active' => ['boolean'],
        ];
    }

    public function updated($property): void
    {
        $this->validateOnly($property);
    }

    public function save(): void
    {
        $validated = $this->validate();

        Department::create($validated);

        session()->flash('toast', [
            'type' => 'success',
            'message' => 'Department created successfully.',
        ]);

        $this->redirectRoute('admin.departments.index', navigate: true);
    }

    public function discard(): void
    {
        $this->reset(['name']);
        $this->is_active = true;
        $this->resetValidation();
    }
};
?>

<div>
    <div class="mb-10">
        <h1 class="text-h1 font-h1 text-on-surface">Create Department</h1>
        <p class="mt-1 text-body-md font-body-md text-secondary">
            Add departments that can be assigned to users.
        </p>
    </div>

    <form wire:submit.prevent="save" class="max-w-7xl">
        <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="mb-8 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">business</span>
                <h3 class="text-h3 font-h2">Department Information</h3>
            </div>

            <div class="space-y-6">
                <div class="space-y-2">
                    <label class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                        Department Name
                    </label>

                    <input type="text" wire:model="name" placeholder="Example: Cloud Infrastructure"
                        class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-body-md font-body-md transition-all focus:border-primary focus:ring-2 focus:ring-primary/10" />

                    @error('name')
                        <p class="text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-label-md font-label-md text-on-surface">Department Status</h4>
                            <p class="mt-1 text-body-sm font-body-sm text-secondary">
                                Inactive departments will not appear in the user create form.
                            </p>
                        </div>

                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="checkbox" wire:model.live="is_active" class="peer sr-only" />

                            <div
                                class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-100">
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <div class="mt-8 flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-end">
                <button type="button" wire:click="discard"
                    class="rounded-lg border border-outline-variant px-5 py-2 text-label-md font-label-md text-on-surface transition-colors hover:bg-slate-50">
                    Discard
                </button>

                <button type="submit" wire:loading.attr="disabled"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2 text-label-md font-label-md text-white shadow-sm transition-opacity hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60">
                    <span wire:loading.remove wire:target="save">Save Department</span>

                    <span wire:loading wire:target="save" class="inline-flex items-center gap-2">
                        <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                        Saving...
                    </span>
                </button>
            </div>
        </div>
    </form>
</div>
