<?php

use App\Models\Blog;
use App\Models\Category;
use App\Models\SiteSetting;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component {
    public Blog $blog;

    public SiteSetting $siteSetting;

    public string $search = '';

    public function mount(string $slug): void
    {
        $this->siteSetting = SiteSetting::current();

        $this->blog = Blog::query()->with('category')->where('slug', $slug)->where('is_active', true)->firstOrFail();
    }

    public function title(): string
    {
        $title = $this->blog->meta_title ?: $this->blog->title ?: 'Blog Details';

        return $title . ' | ' . ($this->siteSetting->site_name ?: config('app.name'));
    }

    public function blogImage(?Blog $blog = null): ?string
    {
        $blog = $blog ?: $this->blog;

        if (blank($blog->thumbnail)) {
            return null;
        }

        if (str_starts_with($blog->thumbnail, 'http://') || str_starts_with($blog->thumbnail, 'https://')) {
            return $blog->thumbnail;
        }

        return asset('storage/' . $blog->thumbnail);
    }

    public function getRecentBlogsProperty()
    {
        return Blog::query()->with('category')->where('is_active', true)->where('id', '!=', $this->blog->id)->latest('published_at')->latest()->limit(3)->get();
    }

    public function getCategoriesProperty()
    {
        return Category::query()
            ->whereHas('blogs', function ($query) {
                $query->where('is_active', true);
            })
            ->withCount([
                'blogs as active_blogs_count' => function ($query) {
                    $query->where('is_active', true);
                },
            ])
            ->orderBy('name')
            ->limit(8)
            ->get();
    }

    public function getKeywordTagsProperty(): array
    {
        return Blog::query()->where('is_active', true)->whereNotNull('tags')->pluck('tags')->flatten()->filter()->unique()->take(12)->values()->toArray();
    }

    public function searchBlogs(): void
    {
        if (blank($this->search)) {
            return;
        }

        $this->redirectRoute(
            'client.blogs',
            [
                'search' => $this->search,
            ],
            navigate: true,
        );
    }

    public function shareUrl(): string
    {
        return route('client.blogs.details', $this->blog->slug);
    }
};
?>

<div class="relative text-white">
    @push('meta')
        <meta name="title"
            content="{{ ($blog->meta_title ?: $blog->title) . ' | ' . ($siteSetting->site_name ?: config('app.name')) }}">
        <meta name="description" content="{{ $blog->meta_description ?: $blog->excerpt }}">
        <meta name="keywords" content="{{ $blog->meta_keywords }}">
    @endpush

    <!-- Hero -->
    <section class="relative overflow-hidden py-10 sm:py-20 lg:pt-28">
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute left-[6%] top-8 h-44 w-44 rounded-full bg-cyan-400/10 blur-3xl"></div>
        </div>

        <div class="relative mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl">
                <div class="flex flex-wrap items-center gap-3 text-xs text-blue-100/55 sm:text-sm">
                    <span class="rounded-full border border-cyan-300/20 bg-cyan-400/10 px-3 py-1 text-cyan-200">
                        {{ $blog->category?->name ?? 'Blog' }}
                    </span>

                    @if ($blog->published_at)
                        <span>{{ $blog->published_at->format('M d, Y') }}</span>
                    @endif

                    @if ($blog->author_name)
                        <span>•</span>
                        <span>{{ $blog->author_name }}</span>
                    @endif
                </div>

                <h1
                    class="mt-6 text-4xl font-extrabold leading-tight tracking-tight text-white sm:text-5xl lg:text-7xl">
                    {{ $blog->title }}
                </h1>

                @if ($blog->excerpt)
                    <p class="mt-6 max-w-3xl text-sm leading-7 text-blue-100/72 sm:text-base sm:leading-8">
                        {{ $blog->excerpt }}
                    </p>
                @endif
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <section class="relative overflow-hidden pb-20 sm:pb-24">
        <div class="mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div class="grid gap-8 lg:grid-cols-[1fr_350px] xl:grid-cols-[1fr_390px]">
                <!-- Article -->
                <article class="space-y-8">
                    <!-- Featured Image -->
                    @if ($this->blogImage())
                        <div
                            class="overflow-hidden rounded-[32px] border border-white/10 bg-white/6 p-4 backdrop-blur-2xl shadow-[0_20px_60px_rgba(0,0,0,0.18)]">
                            <div class="overflow-hidden rounded-[26px] border border-white/10">
                                <img src="{{ $this->blogImage() }}" alt="{{ $blog->title }}"
                                    class="h-[280px] w-full object-cover sm:h-[420px] lg:h-[520px]">
                            </div>
                        </div>
                    @endif

                    <!-- Content -->
                    <div class="blog-details-card">
                        {!! $blog->content !!}
                    </div>

                    <!-- Tags + Share -->
                    <div class="blog-details-card">
                        @if (!empty($blog->tags))
                            <div>
                                <h3 class="text-lg font-semibold text-white">Keyword Tags</h3>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    @foreach ($blog->tags as $tag)
                                        <span class="blog-tag">
                                            {{ is_array($tag) ? $tag['name'] ?? ($tag['title'] ?? '') : $tag }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="{{ !empty($blog->tags) ? 'mt-8' : '' }}">
                            <h3 class="text-lg font-semibold text-white">Share Article</h3>

                            <div class="mt-4 flex items-center gap-3">
                                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($this->shareUrl()) }}"
                                    target="_blank" class="share-btn">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                        fill="currentColor" class="bi bi-facebook" viewBox="0 0 16 16">
                                        <path
                                            d="M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0-.002 3.603-.002 8.05c0 4.017 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.934 6.75-7.951" />
                                    </svg>
                                </a>

                                <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($this->shareUrl()) }}"
                                    target="_blank" class="share-btn">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                        fill="currentColor" class="bi bi-linkedin" viewBox="0 0 16 16">
                                        <path
                                            d="M0 1.146C0 .513.526 0 1.175 0h13.65C15.474 0 16 .513 16 1.146v13.708c0 .633-.526 1.146-1.175 1.146H1.175C.526 16 0 15.487 0 14.854zm4.943 12.248V6.169H2.542v7.225zm-1.2-8.212c.837 0 1.358-.554 1.358-1.248-.015-.709-.52-1.248-1.342-1.248S2.4 3.226 2.4 3.934c0 .694.521 1.248 1.327 1.248zm4.908 8.212V9.359c0-.216.016-.432.08-.586.173-.431.568-.878 1.232-.878.869 0 1.216.662 1.216 1.634v3.865h2.401V9.25c0-2.22-1.184-3.252-2.764-3.252-1.274 0-1.845.7-2.165 1.193v.025h-.016l.016-.025V6.169h-2.4c.03.678 0 7.225 0 7.225z" />
                                    </svg>

                                </a>

                                <a href="https://twitter.com/intent/tweet?url={{ urlencode($this->shareUrl()) }}&text={{ urlencode($blog->title) }}"
                                    target="_blank" class="share-btn">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                        fill="currentColor" class="bi bi-twitter-x" viewBox="0 0 16 16">
                                        <path
                                            d="M12.6.75h2.454l-5.36 6.142L16 15.25h-4.937l-3.867-5.07-4.425 5.07H.316l5.733-6.57L0 .75h5.063l3.495 4.633L12.601.75Zm-.86 13.028h1.36L4.323 2.145H2.865z" />
                                    </svg>
                                </a>

                                <a href="https://wa.me/?text={{ urlencode($blog->title . ' - ' . $this->shareUrl()) }}"
                                    target="_blank" class="share-btn">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                        fill="currentColor" class="bi bi-whatsapp" viewBox="0 0 16 16">
                                        <path
                                            d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232" />
                                    </svg>

                                </a>
                            </div>
                        </div>
                    </div>
                </article>

                <!-- Sidebar -->
                <aside class="space-y-6">
                    <!-- Search -->
                    <div class="blog-sidebar-card">
                        <h3 class="text-2xl font-bold text-white">Search Blog</h3>

                        <form wire:submit.prevent="searchBlogs" class="mt-6">
                            <div class="relative">
                                <input type="text" wire:model="search" placeholder="Search articles..."
                                    class="blog-search-input pr-12" />

                                <button type="submit"
                                    class="absolute right-2 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full border border-white/10 bg-white/8 text-blue-100/70 transition hover:bg-white/12 hover:text-white">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                                    </svg>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Categories -->
                    @if ($this->categories->count())
                        <div class="blog-sidebar-card">
                            <h3 class="text-2xl font-bold text-white">Categories</h3>

                            <div class="mt-6 space-y-3">
                                @foreach ($this->categories as $category)
                                    <a href="{{ route('client.blogs', ['category' => $category->slug ?? $category->id]) }}"
                                        wire:navigate class="blog-category-item">
                                        <span>{{ $category->name }}</span>
                                        <span>{{ $category->active_blogs_count }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Recent Posts -->
                    @if ($this->recentBlogs->count())
                        <div class="blog-sidebar-card">
                            <h3 class="text-2xl font-bold text-white">Recent Posts</h3>

                            <div class="mt-6 space-y-4">
                                @foreach ($this->recentBlogs as $recentBlog)
                                    <a href="{{ route('client.blogs.details', $recentBlog->slug) }}" wire:navigate
                                        class="recent-post-item">

                                        @if ($this->blogImage($recentBlog))
                                            <img src="{{ $this->blogImage($recentBlog) }}"
                                                alt="{{ $recentBlog->title }}"
                                                class="h-18 w-18 rounded-2xl object-cover">
                                        @else
                                            <div
                                                class="flex h-18 w-18 shrink-0 items-center justify-center rounded-2xl border border-white/10 bg-white/8 text-cyan-200">
                                                <span class="material-symbols-outlined text-[22px]">
                                                    article
                                                </span>
                                            </div>
                                        @endif

                                        <div>
                                            <h4 class="text-sm font-semibold leading-6 text-white">
                                                {{ Str::limit($recentBlog->title, 58) }}
                                            </h4>

                                            @if ($recentBlog->published_at)
                                                <p class="mt-1 text-xs text-blue-100/50">
                                                    {{ $recentBlog->published_at->format('M d, Y') }}
                                                </p>
                                            @endif
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Keyword Tags -->
                    @if (!empty($this->keywordTags))
                        <div class="blog-sidebar-card">
                            <h3 class="text-2xl font-bold text-white">Keyword Tags</h3>

                            <div class="mt-6 flex flex-wrap gap-2">
                                @foreach ($this->keywordTags as $tag)
                                    <a href="{{ route('client.blogs', ['tag' => $tag]) }}" wire:navigate
                                        class="blog-tag">
                                        {{ $tag }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </aside>
            </div>
        </div>
    </section>
</div>
