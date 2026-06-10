<?php

use App\Models\LiveTvChannel;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.admin-app')] #[Title('Add Live TV Channel')] class extends Component {
    public string $name = '';
    public string $url = '';
    public string $category = 'Bangladeshi';
    public bool $is_active = true;
    public int $sort_order = 0;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string', 'max:500'],
            'category' => ['required', 'string', 'in:Bangladeshi,Sports,News,Entertainment'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ];
    }

    public function updated($property): void
    {
        $this->validateOnly($property);
    }

    public function save(): void
    {
        $validated = $this->validate();
        LiveTvChannel::create($validated);
        session()->flash('toast', ['type' => 'success', 'message' => 'Channel created successfully.']);
        $this->redirectRoute('admin.live-tv-channels.index', navigate: true);
    }

    public function discard(): void
    {
        $this->reset(['name', 'url']);
        $this->category = 'Bangladeshi';
        $this->is_active = true;
        $this->sort_order = 0;
        $this->resetValidation();
    }
};
?>

<div>
    <div class="mb-10">
        <h1 class="text-h1 font-h1 text-on-surface">Add Live TV Channel</h1>
        <p class="mt-1 text-body-md font-body-md text-secondary">Add a new channel to the Live TV page.</p>
    </div>

    <div class="flex flex-col gap-8 lg:flex-row">
        <div class="min-w-0 flex-1">
            <form wire:submit.prevent="save" class="max-w-7xl">
                <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                    <div class="mb-8 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">live_tv</span>
                        <h3 class="text-h3 font-h2">Channel Details</h3>
                    </div>

                    <div class="space-y-6">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div class="space-y-2">
                                <label
                                    class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">Channel
                                    Name</label>
                                <input type="text" wire:model="name" placeholder="e.g. Somoy TV"
                                    class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-body-md font-body-md transition-all focus:border-primary focus:ring-2 focus:ring-primary/10" />
                                @error('name')
                                    <p class="text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-2">
                                <label
                                    class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">Category</label>
                                <select wire:model="category"
                                    class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-body-md font-body-md transition-all focus:border-primary focus:ring-2 focus:ring-primary/10">
                                    <option value="Bangladeshi">Bangladeshi</option>
                                    <option value="Sports">Sports</option>
                                    <option value="News">News</option>
                                    <option value="Entertainment">Entertainment</option>
                                </select>
                                @error('category')
                                    <p class="text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">Stream
                                URL</label>
                            <input type="url" wire:model="url" placeholder="https://example.com/stream.m3u8"
                                class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-body-md font-body-md transition-all focus:border-primary focus:ring-2 focus:ring-primary/10" />
                            @error('url')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div class="space-y-2">
                                <label class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">Sort
                                    Order</label>
                                <input type="number" wire:model="sort_order" min="0" placeholder="0"
                                    class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-body-md font-body-md transition-all focus:border-primary focus:ring-2 focus:ring-primary/10" />
                                @error('sort_order')
                                    <p class="text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="text-label-md font-label-md text-on-surface">Active</h4>
                                        <p class="mt-1 text-body-sm font-body-sm text-secondary">Inactive channels are
                                            hidden.</p>
                                    </div>
                                    <label class="relative inline-flex cursor-pointer items-center">
                                        <input type="checkbox" wire:model.live="is_active" class="peer sr-only" />
                                        <div
                                            class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary peer-checked:after:translate-x-full peer-checked:after:border-white">
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="mt-8 flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-end">
                        <button type="button" wire:click="discard"
                            class="rounded-lg border border-outline-variant px-5 py-2 text-label-md font-label-md text-on-surface transition-colors hover:bg-slate-50 cursor-pointer">Discard</button>
                        <button type="submit" wire:loading.attr="disabled"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2 text-label-md font-label-md text-white shadow-sm transition-opacity hover:opacity-90 disabled:opacity-60 cursor-pointer">
                            <span wire:loading.remove wire:target="save">Save Channel</span>
                            <span wire:loading wire:target="save" class="inline-flex items-center gap-2">
                                <span
                                    class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                Saving...
                            </span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Reference Panel -- right side --}}
        <div class="w-full shrink-0 lg:w-96">
            <div class="sticky top-24 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-lg">help</span>
                    <h3 class="text-label-md font-label-md text-on-surface">Channel URL Reference</h3>
                </div>
                <p class="mb-4 text-body-sm text-secondary">Click the <span
                        class="material-symbols-outlined text-[14px] align-middle">content_copy</span> icon to copy a
                    URL, then paste into the Stream URL field.</p>

                <div x-data="{ refCategory: 'Bangladeshi' }">
                    <div class="mb-3 flex gap-1 overflow-x-auto">
                        @foreach (['Bangladeshi', 'Sports', 'News', 'Entertainment'] as $cat)
                            <button type="button" @click="refCategory = '{{ $cat }}'"
                                :class="refCategory === '{{ $cat }}'
                                    ?
                                    'bg-primary/10 text-primary border-primary/30' :
                                    'bg-slate-50 text-slate-500 border-slate-200 hover:bg-slate-100'"
                                class="shrink-0 rounded-lg border px-3 py-1.5 text-[11px] font-semibold uppercase tracking-wider transition">
                                {{ $cat }}
                            </button>
                        @endforeach
                    </div>

                    @foreach ([
        'Bangladeshi' => [
            ['n' => 'Somoy TV', 'u' => 'https://owrcovcrpy.gpcdn.net/bpk-tv/1702/output/index.m3u8'],
            ['n' => 'Channel 24', 'u' => 'https://owrcovcrpy.gpcdn.net/bpk-tv/1703/output/index.m3u8'],
            ['n' => 'Jamuna TV', 'u' => 'https://owrcovcrpy.gpcdn.net/bpk-tv/1701/output/index.m3u8'],
            ['n' => 'Independent TV', 'u' => 'https://owrcovcrpy.gpcdn.net/bpk-tv/1704/output/index.m3u8'],
            ['n' => 'ATN Bangla', 'u' => 'https://owrcovcrpy.gpcdn.net/bpk-tv/1722/output/index.m3u8'],
            ['n' => 'Maasranga', 'u' => 'https://owrcovcrpy.gpcdn.net/bpk-tv/1730/output/index.m3u8'],
            ['n' => 'Gazi TV', 'u' => 'http://tvn1.chowdhury-shaheb.com/gazitv/index.m3u8'],
            ['n' => 'Deepto TV', 'u' => 'https://owrcovcrpy.gpcdn.net/bpk-tv/1711/output/index.m3u8'],
            ['n' => 'NTV', 'u' => 'https://owrcovcrpy.gpcdn.net/bpk-tv/1716/output/index.m3u8'],
            ['n' => 'ATN News', 'u' => 'https://owrcovcrpy.gpcdn.net/bpk-tv/1706/output/index.m3u8'],
            ['n' => 'Desh TV', 'u' => 'https://bozztv.com/rongo/rongo-DeshTV/tracks-v1a1/mono.m3u8'],
            ['n' => 'Duronto TV', 'u' => 'https://owrcovcrpy.gpcdn.net/bpk-tv/1713/output/index.m3u8'],
            ['n' => 'Ekushey TV', 'u' => 'http://210.4.72.204/hls-live/livepkgr/_definst_/liveevent/livestream3.m3u8'],
            ['n' => 'DBC News', 'u' => 'https://owrcovcrpy.gpcdn.net/bpk-tv/1728/output/index.m3u8'],
            ['n' => 'Channel 9', 'u' => 'https://owrcovcrpy.gpcdn.net/bpk-tv/1729/output/index.m3u8'],
            ['n' => 'Boishakhi', 'u' => 'https://owrcovcrpy.gpcdn.net/bpk-tv/1707/output/index.m3u8'],
            ['n' => 'Banglavision', 'u' => 'https://owrcovcrpy.gpcdn.net/bpk-tv/1715/output/index.m3u8'],
            ['n' => 'BTV', 'u' => 'https://owrcovcrpy.gpcdn.net/bpk-tv/1709/output/index.m3u8'],
            ['n' => 'Mohona TV', 'u' => 'http://103.229.254.25:7001/play/a05t/index.m3u8'],
            ['n' => 'My TV', 'u' => 'https://owrcovcrpy.gpcdn.net/bpk-tv/1725/output/index.m3u8'],
            ['n' => 'SA TV', 'u' => 'https://owrcovcrpy.gpcdn.net/bpk-tv/1720/output/1720.m3u8'],
            ['n' => 'RTV', 'u' => 'http://tvn3.chowdhury-shaheb.com/rtv/index.m3u8'],
            ['n' => 'News 24', 'u' => 'https://owrcovcrpy.gpcdn.net/bpk-tv/1708/output/index.m3u8'],
            ['n' => 'Gaan Bangla', 'u' => 'https://owrcovcrpy.gpcdn.net/bpk-tv/1714/output/index.m3u8'],
            ['n' => 'Sangsad TV', 'u' => 'https://owrcovcrpy.gpcdn.net/bpk-tv/1710/output/index.m3u8'],
            ['n' => 'Channel I', 'u' => 'https://owrcovcrpy.gpcdn.net/bpk-tv/1723/output/index.m3u8'],
            ['n' => 'Nagorik TV', 'u' => 'http://198.195.239.50:8095/nagorik/tracks-v1a1/mono.m3u8'],
            ['n' => 'Ekattor', 'u' => 'https://owrcovcrpy.gpcdn.net/bpk-tv/1705/output/index.m3u8'],
            ['n' => 'Asian TV', 'u' => 'https://owrcovcrpy.gpcdn.net/bpk-tv/1712/output/index.m3u8'],
            ['n' => 'ATN Music', 'u' => 'https://owrcovcrpy.gpcdn.net/bpk-tv/1717/output/index.m3u8'],
            ['n' => 'Islamic TV', 'u' => 'https://owrcovcrpy.gpcdn.net/bpk-tv/1724/output/index.m3u8'],
        ],
        'Sports' => [['n' => 'T Sports', 'u' => 'https://live.tsports.com/mobile_hls/tsports_live_1/playlist.m3u8'], ['n' => 'Star Sports 1', 'u' => 'http://41.205.93.154/STARSPORTS1/index.m3u8'], ['n' => 'Sony Ten 2', 'u' => 'http://103.229.254.25:7001/play/a0ef/index.m3u8'], ['n' => 'Sony Ten 3', 'u' => 'http://103.99.249.139/sonyten3/index.m3u8'], ['n' => 'A Sports', 'u' => 'http://163.61.227.29:8000/play/a03t/58044238.m3u8'], ['n' => 'DD Sports', 'u' => 'http://103.199.161.254/Content/ddsports/Live/Channel(DDSPORTS)/index.m3u8'], ['n' => 'PTV Sports', 'u' => 'http://163.61.227.29:8000/play/a05l/40652310.m3u8'], ['n' => 'Euro Sports', 'u' => 'http://103.161.153.165:8000/play/a01b/index.m3u8'], ['n' => 'Edge Sports', 'u' => 'https://imgedge.akamaized.net/amagi_hls_data_imgAAA2AA-edgesports/CDN/playlist.m3u8']],
        'News' => [['n' => 'Al Jazeera', 'u' => 'https://owrcovcrpy.gpcdn.net/bpk-tv/1721/output/index.m3u8'], ['n' => 'BBC World', 'u' => 'http://cdns.jp-primehome.com:8000/zhongying/live/playlist.m3u8?cid=cs15'], ['n' => 'CNN', 'u' => 'https://cnn-cnninternational-1-de.samsung.wurl.com/manifest/playlist.m3u8'], ['n' => 'NDTV 24x7', 'u' => 'https://ndtv24x7elemarchana.akamaized.net/hls/live/2003678/ndtv24x7/ndtv24x7master.m3u8'], ['n' => 'DW News', 'u' => 'https://dwstream4-lh.akamaihd.net/i/dwstream4_live@124430/index_1200av.m3u8'], ['n' => 'France 24', 'u' => 'http://f24hls-i.akamaihd.net/hls/live/221147/F24_EN_HI_HLS/master_2000.m3u8'], ['n' => 'CGTN', 'u' => 'https://news.cgtn.com/resource/live/english/cgtn-news.m3u8']],
        'Entertainment' => [
            ['n' => 'Zee Bangla', 'u' => 'http://103.161.153.165:8000/play/zeebnhd/index.m3u8'],
            ['n' => 'Colors Bangla', 'u' => 'http://103.229.254.25:7001/play/a076/index.m3u8'],
            ['n' => 'Star Jalsha', 'u' => 'http://198.195.239.50:8095/bdixbd.net_StarJalshaHD/video.m3u8'],
            ['n' => 'Star Plus', 'u' => 'http://103.229.254.25:7001/play/a09z/index.m3u8'],
            ['n' => 'Zee TV', 'u' => 'http://103.161.153.165:8000/play/a01j/index.m3u8'],
            ['n' => 'Colors', 'u' => 'http://103.229.254.25:7001/play/a077/index.m3u8'],
            ['n' => 'Star Gold', 'u' => 'http://103.99.249.139/stargold/tracks-v1a1/mono.m3u8'],
            ['n' => 'Sangeet Bangla', 'u' => 'http://103.229.254.25:7001/play/a07l/index.m3u8'],
            ['n' => 'Zee Cinema', 'u' => 'http://103.99.249.139/zeecinema/index.m3u8'],
            ['n' => 'History TV', 'u' => 'http://103.229.254.25:7001/play/a02z/index.m3u8'],
            ['n' => 'Nat Geo', 'u' => 'http://103.229.254.25:7001/play/a038/index.m3u8'],
            ['n' => 'Sony Aath', 'u' => 'http://198.195.239.50:8095/SonyAath/tracks-v1a1/mono.m3u8'],
        ],
    ] as $catName => $channels)
                        <div x-show="refCategory === '{{ $catName }}'" x-cloak>
                            <div class="space-y-2 max-h-[450px] overflow-y-auto pr-1">
                                @foreach ($channels as $ch)
                                    <div
                                        class="group flex items-center justify-between rounded-lg border border-slate-100 bg-slate-50 px-3 py-2 text-sm">
                                        <span
                                            class="min-w-[80px] font-medium text-on-surface">{{ $ch['n'] }}</span>
                                        <div class="flex items-center gap-2 min-w-0">
                                            <code
                                                class="max-w-[140px] truncate text-xs text-secondary">{{ $ch['u'] }}</code>
                                            <button type="button"
                                                @click="navigator.clipboard.writeText('{{ $ch['u'] }}'); $el.querySelector('span').textContent='Copied!'; setTimeout(() => $el.querySelector('span').textContent='content_copy', 1500)"
                                                class="shrink-0 text-slate-400 transition hover:text-primary">
                                                <span class="material-symbols-outlined text-[16px]">content_copy</span>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
