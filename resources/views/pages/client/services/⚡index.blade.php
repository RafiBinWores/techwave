<?php

use App\Events\ContactMessageSubmitted;
use App\Mail\ContactMessageMail;
use App\Models\ContactMessage;
use App\Models\Service;
use App\Models\ServicePageSetting;
use App\Models\SiteSetting;
use App\Support\ServicePageLayout;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Services | Techwave')] class extends Component {
    public int $perPage = 12;

    public SiteSetting $siteSetting;

    public string $layoutStyle = 'bento';

    public array $hero = [];

    public array $process = [];

    public array $whyChooseUs = [];

    public array $cta = [];

    public string $full_name = '';
    public string $phone = '';
    public string $email = '';
    public string $company_name = '';
    public string $message = '';
    public string $service_id = '';

    public string $serviceSearch = '';

    public function mount(): void
    {
        $this->siteSetting = SiteSetting::current();

        $resolved = ServicePageSetting::resolved();

        $this->layoutStyle = ServicePageLayout::normalize($resolved['layout']['layout_style'] ?? 'bento');
        $this->hero = $resolved['hero'];
        $this->process = $resolved['process'];
        $this->whyChooseUs = $resolved['why_choose_us'];
        $this->cta = $resolved['cta'];
    }

    public function getFilteredServicesProperty()
    {
        return Service::query()
            ->where('is_active', true)
            ->when($this->serviceSearch, function ($query) {
                $query->where('card_title', 'like', '%' . $this->serviceSearch . '%');
            })
            ->orderBy('card_title')
            ->limit(8)
            ->get();
    }

    public function submitInquiry(): void
    {
        $validated = $this->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'service_id' => ['required', 'exists:services,id'],
            'message' => ['nullable', 'string', 'max:3000'],
        ]);

        $service = Service::find($validated['service_id']);
        $subject = $service ? 'Service Inquiry: ' . $service->card_title : 'Service Inquiry';

        $contactMessage = ContactMessage::create([
            'name' => $validated['full_name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'subject' => $subject,
            'message' => trim(($validated['company_name'] ? 'Company: ' . $validated['company_name'] . "\n\n" : '') . ($validated['message'] ?? '')),
        ]);

        $adminEmail = $this->siteSetting->email ?: config('mail.from.address');

        if ($adminEmail) {
            Mail::to($adminEmail)->send(new ContactMessageMail($contactMessage));
        }

        ContactMessageSubmitted::dispatch($contactMessage);

        $this->reset(['full_name', 'phone', 'email', 'company_name', 'message', 'service_id', 'serviceSearch']);

        $this->dispatch('toast', message: 'Your message has been sent successfully. Our team will get back to you.', type: 'success');
    }

    public function loadMore(): void
    {
        $this->perPage += 12;
    }

    public function getServicesProperty()
    {
        return Service::query()->with('category', 'serviceOptions')->where('is_active', true)->latest()->limit($this->perPage)->get();
    }

    public function getTotalServicesProperty(): int
    {
        return Service::query()->where('is_active', true)->count();
    }

    public function serviceImage(Service $service): string
    {
        if ($service->image) {
            if (str_starts_with($service->image, 'http://') || str_starts_with($service->image, 'https://')) {
                return $service->image;
            }

            return asset('storage/' . $service->image);
        }

        return 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1200&q=80';
    }

    public function serviceMediaStyle(Service $service): ?string
    {
        return $service->media_background_style;
    }

    public function serviceBullets(Service $service): array
    {
        if (!empty($service->included_items)) {
            return collect($service->included_items)
                ->take(3)
                ->map(function ($item) {
                    if (is_array($item)) {
                        return $item['title'] ?? ($item['name'] ?? ($item['text'] ?? null));
                    }

                    return $item;
                })
                ->filter()
                ->values()
                ->toArray();
        }

        if (!empty($service->benefits)) {
            return collect($service->benefits)
                ->take(3)
                ->map(function ($item) {
                    if (is_array($item)) {
                        return $item['title'] ?? ($item['name'] ?? null);
                    }

                    return $item;
                })
                ->filter()
                ->values()
                ->toArray();
        }

        return ['Professional setup', 'Reliable support', 'Business-ready solution'];
    }
};
?>

<div class="relative text-white">

    <!-- Main Services -->
    @if ($hero['enabled'] ?? true)
    <section id="service-list" class="relative overflow-hidden py-20 sm:py-24">
        <div class="mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div class="mb-14 text-center lg:mb-18">
                @if (!empty($hero['badge']))
                <div
                    class="mx-auto mb-5 inline-flex items-center justify-center gap-2 rounded-full glass-chip px-4 py-2 text-xs sm:text-sm text-blue-100/85">
                    <span class="h-2 w-2 rounded-full bg-cyan-300 animate-pulse"></span>
                    {{ $hero['badge'] }}
                </div>
                @endif

                <h2 class="text-3xl font-bold text-white sm:text-4xl lg:text-5xl">
                    {{ $hero['title'] }}
                    @if (!empty($hero['highlighted_title']))
                    <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">
                        {{ $hero['highlighted_title'] }}
                    </span>
                    @endif
                </h2>

                @if (!empty($hero['description']))
                <p class="mx-auto mt-4 max-w-2xl text-sm leading-7 text-blue-100/70 sm:text-base">
                    {{ $hero['description'] }}
                </p>
                @endif
            </div>

            <div class="{{ ServicePageLayout::gridClass($this->layoutStyle) }}">

                @forelse ($this->services as $index => $service)
                @if (ServicePageLayout::isCards($this->layoutStyle))
                <a href="{{ $service->serviceOptions->count()
                            ? route('client.services.options', ['slug' => $service->slug])
                            : route('client.services.details', ['slug' => $service->slug]) }}" wire:navigate
                    class="group relative flex flex-col overflow-hidden rounded-2xl border border-white/10 shadow-2xl shadow-blue-950/20 transition-all duration-300 hover:-translate-y-1 hover:border-cyan-300/30 hover:shadow-cyan-950/30">

                    @if ($this->serviceMediaStyle($service))
                        <div class="absolute inset-0" style="{{ $this->serviceMediaStyle($service) }}">
                        </div>
                    @else
                        <img src="{{ $this->serviceImage($service) }}" alt="{{ $service->card_title }}"
                            class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 group-hover:scale-110">
                    @endif

                    <div class="absolute inset-0 bg-linear-to-t from-slate-950 via-slate-950/70 to-slate-950/20">
                    </div>

                    <div class="relative z-10 flex flex-1 flex-col justify-between gap-4 p-6">
                        <div>
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-slate-950/30 text-cyan-200 backdrop-blur-md">
                                    @if ($service->icon)
                                    <span class="material-symbols-outlined">{{ $service->icon }}</span>
                                    @else
                                    <span class="material-symbols-outlined">apps</span>
                                    @endif
                                </div>

                                <span
                                    class="inline-flex items-center rounded-full border border-white/10 bg-slate-950/30 px-3 py-1 text-xs font-semibold text-cyan-100 backdrop-blur-md">
                                    {{ $service->category?->name ?? 'Service' }}
                                </span>
                            </div>

                            <h3 class="mt-4 text-xl font-bold text-white">
                                {{ $service->card_title }}
                            </h3>

                            @if ($service->show_short_description)
                            <p class="mt-2 text-sm leading-6 text-blue-100/75">
                                {{ Str::limit($service->short_description, 120) }}
                            </p>
                            @endif
                        </div>

                        <div class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-cyan-100">
                            {{ $service->serviceOptions->count() ? 'Explore Options' : 'Explore Service' }}
                            <span
                                class="material-symbols-outlined text-[18px] transition-transform duration-300 group-hover:translate-x-1">
                                arrow_forward
                            </span>
                        </div>
                    </div>
                </a>
                @elseif (ServicePageLayout::isList($this->layoutStyle))
                <a href="{{ $service->serviceOptions->count()
                            ? route('client.services.options', ['slug' => $service->slug])
                            : route('client.services.details', ['slug' => $service->slug]) }}" wire:navigate
                    class="group flex flex-col overflow-hidden rounded-3xl border border-white/10 bg-white/5 shadow-2xl shadow-blue-950/20 transition-all duration-300 hover:-translate-y-1 hover:border-cyan-300/30 hover:shadow-cyan-950/30 md:flex-row">

                    <div class="relative h-56 w-full shrink-0 overflow-hidden md:h-auto md:w-80">
                        @if ($this->serviceMediaStyle($service))
                            <div class="absolute inset-0" style="{{ $this->serviceMediaStyle($service) }}">
                            </div>
                        @else
                            <img src="{{ $this->serviceImage($service) }}" alt="{{ $service->card_title }}"
                                class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 group-hover:scale-110">
                        @endif
                        <div class="absolute inset-0 bg-linear-to-t from-slate-950 via-slate-950/40 to-transparent md:bg-linear-to-r">
                        </div>
                    </div>

                    <div class="flex flex-1 flex-col justify-between gap-6 p-6 md:p-8">
                        <div class="flex items-start justify-between gap-4">
                            <span
                                class="inline-flex items-center rounded-full border border-white/10 bg-slate-950/30 px-3 py-1 text-xs font-semibold text-cyan-100 backdrop-blur-md">
                                {{ $service->category?->name ?? 'Service' }}
                            </span>

                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl border border-white/10 bg-slate-950/30 text-cyan-200 backdrop-blur-md">
                                @if ($service->icon)
                                <span class="material-symbols-outlined">{{ $service->icon }}</span>
                                @else
                                <span class="material-symbols-outlined">apps</span>
                                @endif
                            </div>
                        </div>

                        <div>
                            <h3 class="text-2xl font-bold text-white">
                                {{ $service->card_title }}
                            </h3>

                            @if ($service->show_short_description)
                            <p class="mt-3 text-sm leading-7 text-blue-100/75">
                                {{ Str::limit($service->short_description, 145) }}
                            </p>
                            @endif

                            @if (ServicePageLayout::showsBenefits($this->layoutStyle) && $service->show_benefits)
                            <ul class="mt-6 space-y-3 text-sm text-blue-50/85">
                                @foreach ($this->serviceBullets($service) as $bullet)
                                <li class="service-bullet">{{ $bullet }}</li>
                                @endforeach
                            </ul>
                            @endif

                            <div class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-cyan-100">
                                {{ $service->serviceOptions->count() ? 'Explore Options' : 'Explore Service' }}
                                <span
                                    class="material-symbols-outlined text-[18px] transition-transform duration-300 group-hover:translate-x-1">
                                    arrow_forward
                                </span>
                            </div>
                        </div>
                    </div>
                </a>
                @else
                <a href="{{ $service->serviceOptions->count()
                        ? route('client.services.options', ['slug' => $service->slug])
                        : route('client.services.details', ['slug' => $service->slug]) }}" wire:navigate
                    class="group relative block {{ ServicePageLayout::minHeightClass($this->layoutStyle) }} {{ ServicePageLayout::cardClass($this->layoutStyle, $index) }} overflow-hidden rounded-3xl border border-white/10 bg-white/5 shadow-2xl shadow-blue-950/20 transition-all duration-300 hover:-translate-y-1 hover:border-cyan-300/30 hover:shadow-cyan-950/30">

                    @if ($this->serviceMediaStyle($service))
                        <div class="absolute inset-0" style="{{ $this->serviceMediaStyle($service) }}">
                        </div>
                    @else
                        <img src="{{ $this->serviceImage($service) }}" alt="{{ $service->card_title }}"
                            class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 group-hover:scale-110">
                    @endif

                    <div class="absolute inset-0 bg-linear-to-t from-slate-950 via-slate-950/75 to-blue-950/20">
                    </div>
                    <div class="absolute inset-0 bg-linear-to-br from-cyan-500/20 via-transparent to-blue-700/20">
                    </div>

                    <div class="relative z-10 flex h-full {{ ServicePageLayout::minHeightClass($this->layoutStyle) }} flex-col justify-between p-6">
                        <div class="flex items-start justify-between gap-4">
                            <span
                                class="inline-flex items-center rounded-full border border-white/10 bg-slate-950/30 px-3 py-1 text-xs font-semibold text-cyan-100 backdrop-blur-md">
                                {{ $service->category?->name ?? 'Service' }}
                            </span>

                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl border border-white/10 bg-slate-950/30 text-cyan-200 backdrop-blur-md">
                                @if ($service->icon)
                                <span class="material-symbols-outlined">{{ $service->icon }}</span>
                                @else
                                <span class="material-symbols-outlined">apps</span>
                                @endif
                            </div>
                        </div>

                        <div>
                            <h3 class="text-2xl font-bold text-white">
                                {{ $service->card_title }}
                            </h3>

                            @if ($service->show_short_description)
                            <p class="mt-3 text-sm leading-7 text-blue-100/75">
                                {{ Str::limit($service->short_description, 145) }}
                            </p>
                            @endif

                            @if (ServicePageLayout::showsBenefits($this->layoutStyle) && $service->show_benefits)
                            <ul class="mt-6 space-y-3 text-sm text-blue-50/85">
                                @foreach ($this->serviceBullets($service) as $bullet)
                                <li class="service-bullet">{{ $bullet }}</li>
                                @endforeach
                            </ul>
                            @endif

                            <div class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-cyan-100">
                                {{ $service->serviceOptions->count() ? 'Explore Options' : 'Explore Service' }}
                                <span
                                    class="material-symbols-outlined text-[18px] transition-transform duration-300 group-hover:translate-x-1">
                                    arrow_forward
                                </span>
                            </div>
                        </div>
                    </div>
                </a>
                @endif
                @empty
                <div class="col-span-full rounded-3xl border border-white/10 bg-white/5 p-10 text-center">
                    <h3 class="text-2xl font-bold text-white">No services found</h3>
                    <p class="mt-3 text-sm text-blue-100/70">
                        Please add active services from your admin panel.
                    </p>
                </div>
                @endforelse

            </div>

            @if ($this->services->count() < $this->totalServices)
                <div class="mt-12 flex justify-center">
                    <button type="button" wire:click="loadMore" wire:loading.attr="disabled"
                        class="inline-flex items-center justify-center gap-2 rounded-full border border-white/10 bg-white/8 px-7 py-3.5 text-sm font-semibold text-white backdrop-blur-xl transition hover:-translate-y-0.5 hover:bg-white/12 disabled:cursor-not-allowed disabled:opacity-60 cursor-pointer">

                        <span wire:loading.remove wire:target="loadMore">Load More Services</span>
                        <span wire:loading wire:target="loadMore">Loading...</span>

                        <span wire:loading.remove wire:target="loadMore" class="material-symbols-outlined text-[18px]">
                            expand_more
                        </span>
                    </button>
                </div>
                @endif
        </div>
    </section>
    @endif

    <!-- Process -->
    @if ($process['enabled'] ?? true)
    <section class="relative overflow-hidden py-20 sm:py-24">
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute left-[8%] top-10 h-40 w-40 rounded-full bg-cyan-400/10 blur-3xl"></div>
            <div class="absolute right-[10%] bottom-8 h-52 w-52 rounded-full bg-blue-500/10 blur-3xl"></div>
        </div>

        <div class="relative mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div class="mb-14 text-center lg:mb-18">
                @if (!empty($process['badge']))
                <div
                    class="mx-auto mb-5 inline-flex items-center justify-center gap-2 rounded-full glass-chip px-4 py-2 text-xs sm:text-sm text-blue-100/85">
                    <span class="h-2 w-2 rounded-full bg-cyan-300 animate-pulse"></span>
                    {{ $process['badge'] }}
                </div>
                @endif

                <h2 class="text-3xl font-bold text-white sm:text-4xl lg:text-5xl">
                    {{ $process['title'] }}
                    @if (!empty($process['highlighted_title']))
                    <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">
                        {{ $process['highlighted_title'] }}
                    </span>
                    @endif
                </h2>

                @if (!empty($process['description']))
                <p class="mx-auto mt-4 max-w-2xl text-sm leading-7 text-blue-100/70 sm:text-base">
                    {{ $process['description'] }}
                </p>
                @endif
            </div>

            <div class="relative">
                <div
                    class="absolute left-1/2 top-0 hidden h-full w-px -translate-x-1/2 bg-linear-to-b from-cyan-300/0 via-cyan-300/30 to-cyan-300/0 lg:block">
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    @foreach ($process['steps'] as $stepIndex => $step)
                    @php
                    $stepColor = match ($stepIndex % 4) {
                    0 => ['icon' => 'bg-cyan-500/15 text-cyan-200'],
                    1 => ['icon' => 'bg-blue-500/15 text-blue-200'],
                    2 => ['icon' => 'bg-sky-500/15 text-sky-200'],
                    default => ['icon' => 'bg-violet-500/15 text-violet-200'],
                    };
                    @endphp

                    @if ($stepIndex % 2 === 0)
                    <div class="lg:pr-8">
                        <div class="process-premium-card lg:mr-8">
                            <div class="process-premium-top">
                                <div class="process-premium-step">{{ str_pad((string) ($stepIndex + 1), 2, '0', STR_PAD_LEFT) }}</div>
                                <div class="process-premium-icon {{ $stepColor['icon'] }}">
                                    <span class="material-symbols-outlined text-[24px]">{{ $step['icon'] ?? 'check_circle' }}</span>
                                </div>
                            </div>

                            <h3 class="mt-6 text-2xl font-bold text-white">{{ $step['title'] }}</h3>
                            <p class="mt-3 text-sm leading-7 text-blue-100/68">{{ $step['description'] }}</p>
                        </div>
                    </div>
                    @else
                    <div class="lg:pt-16 lg:pl-8">
                        <div class="process-premium-card lg:ml-8">
                            <div class="process-premium-top">
                                <div class="process-premium-step">{{ str_pad((string) ($stepIndex + 1), 2, '0', STR_PAD_LEFT) }}</div>
                                <div class="process-premium-icon {{ $stepColor['icon'] }}">
                                    <span class="material-symbols-outlined text-[24px]">{{ $step['icon'] ?? 'check_circle' }}</span>
                                </div>
                            </div>

                            <h3 class="mt-6 text-2xl font-bold text-white">{{ $step['title'] }}</h3>
                            <p class="mt-3 text-sm leading-7 text-blue-100/68">{{ $step['description'] }}</p>
                        </div>
                    </div>
                    @endif
                    @endforeach
                </div>
            </div>
        </div>
    </section>
    @endif

    <!-- Why Choose Us -->
    @if ($whyChooseUs['enabled'] ?? true)
    <section class="relative overflow-hidden py-20 sm:py-24">
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute left-[5%] bottom-10 h-44 w-44 rounded-full bg-cyan-400/10 blur-3xl"></div>
            <div class="absolute right-[8%] top-8 h-56 w-56 rounded-full bg-blue-500/10 blur-3xl"></div>
        </div>

        <div class="relative mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div class="mb-14 text-center">
                @if (!empty($whyChooseUs['badge']))
                <div
                    class="mx-auto mb-5 inline-flex items-center justify-center gap-2 rounded-full glass-chip px-4 py-2 text-xs sm:text-sm text-blue-100/85">
                    <span class="h-2 w-2 rounded-full bg-cyan-300 animate-pulse"></span>
                    {{ $whyChooseUs['badge'] }}
                </div>
                @endif

                <h2 class="text-3xl font-bold text-white sm:text-4xl lg:text-5xl">
                    {{ $whyChooseUs['title'] }}
                    @if (!empty($whyChooseUs['highlighted_title']))
                    <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">
                        {{ $whyChooseUs['highlighted_title'] }}
                    </span>
                    @endif
                </h2>

                @if (!empty($whyChooseUs['description']))
                <p class="mx-auto mt-4 max-w-2xl text-sm leading-7 text-blue-100/70 sm:text-base">
                    {{ $whyChooseUs['description'] }}
                </p>
                @endif
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($whyChooseUs['items'] as $itemIndex => $item)
                @php
                $itemColor = match ($itemIndex % 4) {
                0 => 'bg-cyan-500/15 text-cyan-200',
                1 => 'bg-blue-500/15 text-blue-200',
                2 => 'bg-sky-500/15 text-sky-200',
                default => 'bg-violet-500/15 text-violet-200',
                };
                @endphp

                <div @class(['why-premium-card', 'why-premium-card-featured'=> $itemIndex % 4 === 1])>
                    <div class="why-premium-icon {{ $itemColor }}">
                        <span class="material-symbols-outlined text-[26px]">{{ $item['icon'] ?? 'check_circle' }}</span>
                    </div>

                    <h3 class="mt-6 text-xl font-bold text-white">{{ $item['title'] }}</h3>
                    <p class="mt-3 text-sm leading-7 text-blue-100/68">{{ $item['description'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- Contact CTA Form -->
    @if ($cta['enabled'] ?? true)
    <section class="relative overflow-hidden py-20 sm:py-24">

        <div class="relative mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div
                class="overflow-hidden rounded-[36px] border border-white/15 bg-white/8 shadow-[0_30px_100px_rgba(0,0,0,0.28)] backdrop-blur-2xl">
                <div class="grid grid-cols-1 lg:grid-cols-[1.05fr_0.95fr]">
                    <div class="px-6 py-10 sm:px-8 sm:py-12 lg:px-12 lg:py-14">
                        @if (!empty($cta['badge']))
                        <div
                            class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/6 px-4 py-2 text-xs sm:text-sm text-blue-100/85">
                            <span class="h-2 w-2 rounded-full bg-cyan-300 animate-pulse"></span>
                            {{ $cta['badge'] }}
                        </div>
                        @endif

                        <h2 class="mt-6 text-3xl font-bold text-white sm:text-4xl lg:text-5xl">
                            {{ $cta['title'] }}
                            @if (!empty($cta['highlighted_title']))
                            <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">
                                {{ $cta['highlighted_title'] }}
                            </span>
                            @endif
                        </h2>

                        @if (!empty($cta['description']))
                        <p class="mt-5 max-w-xl text-sm leading-7 text-blue-100/70 sm:text-base">
                            {{ $cta['description'] }}
                        </p>
                        @endif

                        <div class="mt-8 grid gap-4 sm:grid-cols-2">
                            <div class="contact-info-card">
                                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">Email</p>

                                @if ($siteSetting->email)
                                <a href="mailto:{{ $siteSetting->email }}"
                                    class="mt-2 text-sm font-semibold text-white">
                                    {{ $siteSetting->email }}
                                </a>
                                @else
                                <p class="mt-2 text-sm font-semibold text-white">
                                    info@example.com
                                </p>
                                @endif
                            </div>

                            <div class="contact-info-card">
                                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">Phone</p>

                                @if ($siteSetting->phone)
                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $siteSetting->phone) }}"
                                    class="mt-2 text-sm font-semibold text-white">
                                    {{ $siteSetting->phone }}
                                </a>
                                @else
                                <p class="mt-2 text-sm font-semibold text-white">
                                    +8809638-101601
                                </p>
                                @endif
                            </div>

                            <div class="contact-info-card sm:col-span-2">
                                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">Location</p>
                                <p class="mt-2 text-sm font-semibold text-white">
                                    {{ $siteSetting->location ?: 'Business hours support with priority response options' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="border-t border-white/10 bg-slate-950/20 px-6 py-10 sm:px-8 sm:py-12 lg:border-l lg:border-t-0 lg:px-10 lg:py-14">

                        <form wire:submit.prevent="submitInquiry" class="space-y-5">
                            <div class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-blue-50/85">Full Name</label>
                                    <input type="text" wire:model="full_name" placeholder="Enter your name"
                                        class="contact-input">
                                    @error('full_name')
                                    <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium text-blue-50/85">Phone</label>
                                    <input type="text" wire:model="phone" placeholder="Enter your phone"
                                        class="contact-input">
                                    @error('phone')
                                    <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-blue-50/85">Email
                                        Address</label>
                                    <input type="email" wire:model="email" placeholder="Enter your email"
                                        class="contact-input">
                                    @error('email')
                                    <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium text-blue-50/85">
                                        Service Needed
                                    </label>

                                    <div x-data="{
                                        open: false,
                                        selectedService: @entangle('serviceSearch').live,
                                    }" class="relative">
                                        <input type="text" wire:model.live.debounce.300ms="serviceSearch"
                                            @focus="open = true" @click="open = true" @keydown.escape="open = false"
                                            placeholder="Search service..." class="contact-input pr-12">

                                        <input type="hidden" wire:model="service_id">

                                        <!-- Search Icon -->
                                        <div
                                            class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-blue-100/60">
                                            <span class="material-symbols-outlined text-[20px]">
                                                search
                                            </span>
                                        </div>

                                        @if ($serviceSearch)
                                        <button type="button" wire:click="$set('serviceSearch', '')"
                                            wire:click.prevent="$set('service_id', '')"
                                            class="absolute inset-y-0 right-12 flex items-center text-blue-100/60 transition hover:text-white">
                                            <span class="material-symbols-outlined text-[18px]">
                                                close
                                            </span>
                                        </button>
                                        @endif

                                        <div x-show="open" @click.outside="open = false" x-transition
                                            class="absolute left-0 right-0 z-30 mt-2 max-h-64 overflow-y-auto rounded-2xl border border-white/10 bg-slate-950/95 p-2 shadow-2xl shadow-blue-950/40 backdrop-blur-2xl  [&::-webkit-scrollbar]:w-2 [&::-webkit-scrollbar-thumb]:bg-gray-300
     hover:[&::-webkit-scrollbar-thumb]:bg-gray-400 [&::-webkit-scrollbar-thumb]:rounded-full"
                                            style="display: none;">
                                            @forelse ($this->filteredServices as $service)
                                            <button type="button"
                                                wire:click="$set('service_id', '{{ $service->id }}'); $set('serviceSearch', '{{ addslashes($service->card_title) }}')"
                                                @click="open = false"
                                                class="w-full rounded-xl px-4 py-3 text-left transition hover:bg-white/10">
                                                <span class="block text-sm font-semibold text-white">
                                                    {{ $service->card_title }}
                                                </span>

                                                @if ($service->category)
                                                <span class="mt-1 block text-xs text-blue-100/55">
                                                    {{ $service->category->name }}
                                                </span>
                                                @endif
                                            </button>
                                            @empty
                                            <div class="px-4 py-4 text-sm text-blue-100/60">
                                                No service found.
                                            </div>
                                            @endforelse
                                        </div>
                                    </div>

                                    @error('service_id')
                                    <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-blue-50/85">Company Name</label>
                                <input type="text" wire:model="company_name" placeholder="Enter your company name"
                                    class="contact-input">
                                @error('company_name')
                                <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-blue-50/85">Your Message</label>
                                <textarea rows="5" wire:model="message" placeholder="Tell us about your requirements"
                                    class="contact-input resize-none"></textarea>
                                @error('message')
                                <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="submit" wire:loading.attr="disabled" wire:target="submitInquiry"
                                class="inline-flex w-full items-center justify-center rounded-full bg-linear-to-r from-blue-500 to-sky-400 px-6 py-4 text-sm font-semibold text-white shadow-lg shadow-blue-500/30 transition hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-60 cursor-pointer">

                                <span wire:loading.remove wire:target="submitInquiry">
                                    Send Inquiry
                                </span>

                                <span wire:loading wire:target="submitInquiry">
                                    Sending...
                                </span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif
</div>