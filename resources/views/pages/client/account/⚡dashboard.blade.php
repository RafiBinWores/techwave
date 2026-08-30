<?php

use App\Models\CompressedPdf;
use App\Models\MergedPdf;
use App\Models\Order;
use App\Models\Proposal;
use App\Models\SplitPdf;
use App\Models\SupportTicket;
use App\Models\ToolSubscription;
use App\Models\UserBgRemovedImage;
use App\Models\UserCompressedImage;
use App\Models\UserResizedImage;
use App\Services\WhmcsApi;
use App\Services\WhmcsApiException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

new class extends Component
{
    public function greeting(): string
    {
        $hour = (int) now()->format('H');

        if ($hour < 12) {
            return 'Good morning';
        }

        if ($hour < 17) {
            return 'Good afternoon';
        }

        return 'Good evening';
    }

    public function firstName(): string
    {
        $name = trim((string) auth()->user()->name);

        $first = preg_split('/\s+/', $name)[0] ?? '';

        return $first !== ''
            ? $first
            : 'there';
    }

    public function initials(): string
    {
        $name = trim((string) auth()->user()->name);

        $parts = array_values(
            array_filter(
                preg_split('/\s+/', $name)
            )
        );

        $initials = '';

        foreach (
            array_slice($parts, 0, 2)
            as $part
        ) {
            $initials .= strtoupper(
                mb_substr(
                    $part,
                    0,
                    1
                )
            );
        }

        return $initials !== ''
            ? $initials
            : 'U';
    }

    public function formatDate($date): string
    {
        if (! $date) {
            return 'N/A';
        }

        try {
            return Carbon::parse($date)
                ->format('d M Y');
        } catch (\Throwable) {
            return 'N/A';
        }
    }

    public function timeAgo($date): string
    {
        if (! $date) {
            return 'N/A';
        }

        try {
            return Carbon::parse($date)
                ->diffForHumans();
        } catch (\Throwable) {
            return 'N/A';
        }
    }

    public function daysLeft($date): ?int
    {
        if (! $date) {
            return null;
        }

        try {
            return now()
                ->startOfDay()
                ->diffInDays(
                    Carbon::parse($date)
                        ->startOfDay(),
                    false
                );
        } catch (\Throwable) {
            return null;
        }
    }

    public function expiryText($date): string
    {
        if (! $date) {
            return 'Active';
        }

        $days = $this->daysLeft(
            $date
        );

        if ($days === null) {
            return 'N/A';
        }

        if ($days < 0) {
            return 'Expired';
        }

        if ($days === 0) {
            return 'Expires today';
        }

        if ($days === 1) {
            return 'Expires tomorrow';
        }

        return 'Expires in '
            . $days
            . ' days';
    }

    public function orderStatusClass(
        ?string $status
    ): string {
        return match ($status) {
            'active' =>
            'client-badge client-badge-green',

            'paid' =>
            'client-badge client-badge-green',

            'pending' =>
            'client-badge client-badge-yellow',

            'awaiting_payment' =>
            'client-badge client-badge-yellow',

            'completed' =>
            'client-badge client-badge-blue',

            'cancelled' =>
            'client-badge client-badge-red',

            default =>
            'client-badge client-badge-blue',
        };
    }

    public function ticketStatusClass(
        ?string $status
    ): string {
        return match ($status) {
            'open' =>
            'client-badge client-badge-yellow',

            'pending' =>
            'client-badge client-badge-yellow',

            'in_progress' =>
            'client-badge client-badge-blue',

            'answered' =>
            'client-badge client-badge-blue',

            'resolved' =>
            'client-badge client-badge-green',

            'closed' =>
            'client-badge',

            default =>
            'client-badge client-badge-blue',
        };
    }

    public function formatMoney(
        null|string|int|float $amount
    ): string {
        if (
            $amount === null
            || $amount === ''
        ) {
            return '-';
        }

        return number_format(
            (float) $amount,
            2
        )
            . ' '
            . config(
                'app.currency_code',
                'BDT'
            );
    }

    public function serviceStatusClass(
        ?string $status
    ): string {
        return match (strtolower(
            trim(
                (string) $status
            )
        )) {
            'active' =>
            'client-badge client-badge-green',

            'pending' =>
            'client-badge client-badge-yellow',

            'suspended' =>
            'client-badge client-badge-red border border-rose-300/30 bg-rose-400/10 text-rose-300',

            'cancelled',
            'terminated',
            'fraud' =>
            'client-badge client-badge-red border border-rose-300/30 bg-rose-400/10 text-rose-300',

            default =>
            'client-badge client-badge-blue',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Normalize WHMCS GetClientsProducts
    |--------------------------------------------------------------------------
    */

    private function normalizeWhmcsProducts(
        array $response
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Official WHMCS response
        |--------------------------------------------------------------------------
        |
        | products.product
        |
        */

        $products = data_get(
            $response,
            'products.product'
        );

        /*
        |--------------------------------------------------------------------------
        | Already-normalized response fallback
        |--------------------------------------------------------------------------
        */

        if ($products === null) {
            /*
             * If this already looks like one
             * purchased service.
             */

            if (
                isset($response['id'])
                && isset($response['status'])
            ) {
                $products = [
                    $response,
                ];
            }

            /*
             * Or already a numeric list.
             */ elseif (
                array_is_list(
                    $response
                )
            ) {
                $products =
                    $response;
            } else {
                $products = [];
            }
        }

        if (! is_array($products)) {
            return [];
        }

        /*
        |--------------------------------------------------------------------------
        | One service instead of list
        |--------------------------------------------------------------------------
        */

        if (
            isset($products['id'])
            || isset($products['status'])
        ) {
            $products = [
                $products,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Keep valid rows only
        |--------------------------------------------------------------------------
        */

        return array_values(
            array_filter(
                $products,
                fn($product) =>
                is_array($product)
            )
        );
    }

    public function with(): array
    {
        $user = auth()->user();

        $userId = $user->id;

        /*
        |--------------------------------------------------------------------------
        | Proposals
        |--------------------------------------------------------------------------
        */

        $sentProposals =
            Proposal::query()
            ->with('items')
            ->where(
                'user_id',
                $userId
            )
            ->where(
                'status',
                'sent'
            )
            ->where(
                function ($query) {
                    $query
                        ->whereNull(
                            'valid_until'
                        )
                        ->orWhereDate(
                            'valid_until',
                            '>=',
                            now()
                                ->toDateString()
                        );
                }
            )
            ->latest(
                'sent_at'
            )
            ->latest()
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Orders
        |--------------------------------------------------------------------------
        */

        $recentOrders =
            Order::query()
            ->with('service')
            ->where(
                'user_id',
                $userId
            )
            ->latest(
                'updated_at'
            )
            ->latest()
            ->take(5)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Tickets
        |--------------------------------------------------------------------------
        */

        $latestTickets =
            SupportTicket::query()
            ->where(
                'user_id',
                $userId
            )
            ->latest(
                'updated_at'
            )
            ->latest()
            ->take(5)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Order Counts
        |--------------------------------------------------------------------------
        */

        $activeOrders =
            Order::query()
            ->where(
                'user_id',
                $userId
            )
            ->whereIn(
                'status',
                [
                    'active',
                    'paid',
                ]
            )
            ->count();

        $totalOrders =
            Order::query()
            ->where(
                'user_id',
                $userId
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Open Tickets
        |--------------------------------------------------------------------------
        */

        $openTickets =
            SupportTicket::query()
            ->where(
                'user_id',
                $userId
            )
            ->whereIn(
                'status',
                [
                    'open',
                    'pending',
                    'in_progress',
                    'answered',
                ]
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Tool Subscriptions
        |--------------------------------------------------------------------------
        */

        $activeSubscriptions =
            ToolSubscription::query()
            ->with([
                'toolCategory',
                'toolPlan',
            ])
            ->where(
                'user_id',
                $userId
            )
            ->active()
            ->latest()
            ->get();

        $activeToolSubs =
            $activeSubscriptions
            ->count();

        $nextExpiringSubscription =
            $activeSubscriptions
            ->filter(
                fn($sub) =>
                ! empty($sub->expires_at)
            )
            ->sortBy(
                'expires_at'
            )
            ->first();

        $latestOrder =
            $recentOrders
            ->first();

        $latestTicket =
            $latestTickets
            ->first();

        $attentionCount =
            $sentProposals->count()
            + $openTickets;

        /*
        |--------------------------------------------------------------------------
        | Backups
        |--------------------------------------------------------------------------
        */

        $activeBackupCount =
            collect([
                UserCompressedImage::query()
                    ->where(
                        'user_id',
                        $userId
                    )
                    ->where(
                        'expires_at',
                        '>',
                        now()
                    )
                    ->count(),

                UserBgRemovedImage::query()
                    ->where(
                        'user_id',
                        $userId
                    )
                    ->where(
                        'expires_at',
                        '>',
                        now()
                    )
                    ->count(),

                UserResizedImage::query()
                    ->where(
                        'user_id',
                        $userId
                    )
                    ->where(
                        'expires_at',
                        '>',
                        now()
                    )
                    ->count(),

                CompressedPdf::query()
                    ->where(
                        'user_id',
                        $userId
                    )
                    ->where(
                        'is_backup_enabled',
                        true
                    )
                    ->where(
                        'backup_expires_at',
                        '>',
                        now()
                    )
                    ->count(),

                SplitPdf::query()
                    ->where(
                        'user_id',
                        $userId
                    )
                    ->where(
                        'is_backup_enabled',
                        true
                    )
                    ->where(
                        'backup_expires_at',
                        '>',
                        now()
                    )
                    ->count(),

                MergedPdf::query()
                    ->where(
                        'user_id',
                        $userId
                    )
                    ->where(
                        'is_backup_enabled',
                        true
                    )
                    ->where(
                        'backup_expires_at',
                        '>',
                        now()
                    )
                    ->count(),
            ])
            ->sum();

        /*
        |--------------------------------------------------------------------------
        | WHMCS
        |--------------------------------------------------------------------------
        */

        $whmcsAccount =
            $user->whmcsAccount;

        $whmcsStats = [];

        $whmcsServices = [];

        $whmcsActiveServices = [];

        $whmcsServiceError = null;

        /*
        |--------------------------------------------------------------------------
        | WHMCS Account Exists
        |--------------------------------------------------------------------------
        */

        if ($whmcsAccount) {
            $clientId =
                $whmcsAccount
                ->whmcs_client_id;

            /*
            |--------------------------------------------------------------------------
            | Require Client ID
            |--------------------------------------------------------------------------
            */

            if (! $clientId) {
                $whmcsServiceError =
                    'WHMCS Client ID is missing.';

                logger()->warning(
                    'WHMCS dashboard missing client ID.',
                    [
                        'user_id' =>
                        $userId,

                        'whmcs_user_id' =>
                        $whmcsAccount
                            ->whmcs_user_id,
                    ]
                );
            } else {
                $api = app(
                    WhmcsApi::class
                );

                /*
                |--------------------------------------------------------------------------
                | Client Stats
                |--------------------------------------------------------------------------
                */

                $cacheKey =
                    'whmcs-dashboard-client:'
                    . $userId;

                $cachedWhmcs =
                    Cache::get(
                        $cacheKey
                    );

                if (
                    ! is_array(
                        $cachedWhmcs
                    )
                ) {
                    $client = null;

                    try {
                        $client =
                            $api
                            ->getClientDetails(
                                $clientId,
                                $whmcsAccount
                                    ->email
                            );
                    } catch (
                        WhmcsApiException $e
                    ) {
                        logger()->warning(
                            'Unable to retrieve WHMCS client details.',
                            [
                                'user_id' =>
                                $userId,

                                'whmcs_client_id' =>
                                $clientId,

                                'message' =>
                                $e
                                    ->getMessage(),
                            ]
                        );
                    }

                    $cachedWhmcs = [
                        'client' =>
                        $client,
                    ];

                    Cache::put(
                        $cacheKey,
                        $cachedWhmcs,
                        now()
                            ->addMinutes(10)
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Client Stats Response
                |--------------------------------------------------------------------------
                */

                $whmcsStats =
                    (array) (
                        data_get(
                            $cachedWhmcs,
                            'client.stats'
                        )
                        ?? data_get(
                            $cachedWhmcs,
                            'client.client.stats'
                        )
                        ?? []
                    );

                /*
                |--------------------------------------------------------------------------
                | Get Purchased Products Directly
                |--------------------------------------------------------------------------
                |
                | We intentionally call request() directly here.
                |
                | This removes getClientProducts() wrapper ambiguity.
                |
                */

                try {
                    try {
                        $whmcsServices =
                            $api
                            ->getClientProducts(
                                (int) $clientId
                            );
                    } catch (WhmcsApiException) {
                        $whmcsServices =
                            $api
                            ->getClientProductsXml(
                                (int) $clientId
                            );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Normalize Products
                    |--------------------------------------------------------------------------
                    */

                    /*
                    |--------------------------------------------------------------------------
                    | Find Active Services
                    |--------------------------------------------------------------------------
                    */

                    $whmcsActiveServices =
                        array_values(
                            array_filter(
                                $whmcsServices,
                                function (
                                    array $service
                                ): bool {
                                    $status =
                                        strtolower(
                                            trim(
                                                (string) (
                                                    $service['status']
                                                    ?? ''
                                                )
                                            )
                                        );

                                    return $status
                                        === 'active';
                                }
                            )
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | Diagnostic Log
                    |--------------------------------------------------------------------------
                    |
                    | Safe summary only.
                    | No passwords or sensitive service data.
                    |
                    */

                    logger()->info(
                        'WHMCS dashboard products loaded.',
                        [
                            'user_id' =>
                            $userId,

                            'whmcs_client_id' =>
                            $clientId,

                            'normalized_count' =>
                            count(
                                $whmcsServices
                            ),

                            'active_count' =>
                            count(
                                $whmcsActiveServices
                            ),

                            'statuses' =>
                            array_values(
                                array_unique(
                                    array_map(
                                        fn(
                                            array $service
                                        ) =>
                                        (string) (
                                            $service['status']
                                            ?? ''
                                        ),
                                        $whmcsServices
                                    )
                                )
                            ),
                        ]
                    );
                } catch (
                    WhmcsApiException $e
                ) {
                    $whmcsServices = [];

                    $whmcsActiveServices = [];

                    $whmcsServiceError =
                        $e->getMessage();

                    logger()->error(
                        'WHMCS GetClientsProducts failed.',
                        [
                            'user_id' =>
                            $userId,

                            'whmcs_client_id' =>
                            $clientId,

                            'message' =>
                            $e->getMessage(),
                        ]
                    );
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [
            'sentProposals' =>
            $sentProposals,

            'sentProposalCount' =>
            $sentProposals
                ->count(),

            'recentOrders' =>
            $recentOrders,

            'latestTickets' =>
            $latestTickets,

            'activeOrders' =>
            $activeOrders,

            'totalOrders' =>
            $totalOrders,

            'openTickets' =>
            $openTickets,

            'activeSubscriptions' =>
            $activeSubscriptions,

            'activeToolSubs' =>
            $activeToolSubs,

            'nextExpiringSubscription' =>
            $nextExpiringSubscription,

            'latestOrder' =>
            $latestOrder,

            'latestTicket' =>
            $latestTicket,

            'attentionCount' =>
            $attentionCount,

            'activeBackupCount' =>
            $activeBackupCount,

            'whmcsAccount' =>
            $whmcsAccount,

            'whmcsStats' =>
            $whmcsStats,

            'whmcsServices' =>
            $whmcsServices,

            'whmcsActiveServices' =>
            $whmcsActiveServices,

            'whmcsServiceError' =>
            $whmcsServiceError,
        ];
    }
};
?>

<div
    x-data="{ sidebarOpen: false }"
    class="relative min-h-screen text-white">

    <div
        class="mx-auto max-w-350 px-4 py-6 sm:px-6 lg:px-8">

        <div
            class="rounded-2xl border border-white/10 bg-white/6
                   shadow-[0_20px_80px_rgba(0,0,0,0.22)]
                   backdrop-blur-2xl">

            <div
                class="flex min-h-[calc(100vh-3rem)]">

                {{-- Mobile Overlay --}}

                <div
                    x-show="sidebarOpen"
                    x-transition.opacity
                    class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm lg:hidden"
                    @click="sidebarOpen = false"
                    style="display:none;"></div>

                {{-- Sidebar --}}

                <livewire:shared.user-sidebar />

                {{-- Main --}}

                <div
                    class="min-w-0 flex-1 p-4 sm:p-6 lg:p-8">

                    {{-- Header --}}

                    <div
                        class="mb-6 flex flex-col gap-4
                               lg:flex-row lg:items-center lg:justify-between">

                        <div
                            class="flex items-center gap-3">

                            <button
                                type="button"
                                @click="sidebarOpen = true"
                                class="flex h-11 w-11 items-center justify-center
                                       rounded-2xl border border-white/10
                                       bg-white/8 text-white
                                       shadow-[0_10px_30px_rgba(0,0,0,0.18)]
                                       backdrop-blur-xl
                                       transition hover:bg-white/12
                                       lg:hidden">

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-6 w-6"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M4 6h16M4 12h16M4 18h16" />
                                </svg>

                            </button>

                            <div>

                                <h1
                                    class="text-2xl font-bold text-white sm:text-3xl">
                                    Dashboard
                                </h1>

                                <p
                                    class="mt-1 text-sm text-blue-100/50">
                                    Welcome back,
                                    {{ $this->firstName() }}.
                                </p>

                            </div>

                        </div>

                        <div
                            class="flex flex-wrap items-center gap-3">

                            <a
                                href="{{ route('client.tickets.index') }}"
                                wire:navigate
                                class="inline-flex items-center justify-center gap-2
                                       rounded-2xl border border-white/10 bg-white/8
                                       px-4 py-3 text-sm font-semibold text-white
                                       transition hover:bg-white/12">

                                <span
                                    class="material-symbols-outlined text-base">
                                    support_agent
                                </span>

                                Support

                            </a>

                            <a
                                href="{{ route('account.services') }}"
                                wire:navigate
                                class="inline-flex items-center justify-center gap-2
                                       rounded-2xl
                                       bg-linear-to-r from-cyan-500 to-blue-500
                                       px-5 py-3 text-sm font-semibold text-white
                                       shadow-lg shadow-cyan-500/20
                                       transition hover:-translate-y-0.5">

                                <span
                                    class="material-symbols-outlined text-base">
                                    dashboard
                                </span>

                                My Services

                            </a>

                            @if ($whmcsAccount)
                            <a
                                href="{{ route('account.whmcs.sso') }}"
                                class="inline-flex items-center justify-center gap-2
                                       rounded-2xl
                                       bg-linear-to-r from-emerald-500 to-green-600
                                       px-5 py-3 text-sm font-semibold text-white
                                       shadow-lg shadow-emerald-500/20
                                       transition hover:-translate-y-0.5" target="_blank">

                                <span
                                    class="material-symbols-outlined text-base">
                                    account_balance
                                </span>

                                Billing Center

                            </a>
                            @endif

                        </div>

                    </div>

                    {{-- Stats --}}

                    <div
                        class="mb-6 grid grid-cols-2 gap-4 xl:grid-cols-4">

                        <div class="client-card p-5">

                            <p
                                class="text-xs uppercase tracking-wider text-blue-100/45">
                                Active Orders
                            </p>

                            <p
                                class="mt-2 text-3xl font-bold text-white">
                                {{ $activeOrders }}
                            </p>

                        </div>

                        <div class="client-card p-5">

                            <p
                                class="text-xs uppercase tracking-wider text-blue-100/45">
                                Tool Plans
                            </p>

                            <p
                                class="mt-2 text-3xl font-bold text-white">
                                {{ $activeToolSubs }}
                            </p>

                        </div>

                        <div class="client-card p-5">

                            <p
                                class="text-xs uppercase tracking-wider text-blue-100/45">
                                Backed-up Files
                            </p>

                            <p
                                class="mt-2 text-3xl font-bold text-white">
                                {{ $activeBackupCount }}
                            </p>

                        </div>

                        <div class="client-card p-5">

                            <p
                                class="text-xs uppercase tracking-wider text-blue-100/45">
                                Open Tickets
                            </p>

                            <p
                                class="mt-2 text-3xl font-bold text-white">
                                {{ $openTickets }}
                            </p>

                        </div>

                    </div>

                    {{-- Billing Profile --}}

                    @if ($whmcsAccount)

                    <div
                        class="mb-6 rounded-[28px] border border-white/10
                                   bg-white/8 p-6
                                   shadow-[0_16px_50px_rgba(0,0,0,0.18)]
                                   backdrop-blur-2xl">

                        <div
                            class="mb-4 flex items-center justify-between gap-4">

                            <div
                                class="flex items-center gap-4">

                                <div
                                    class="flex h-12 w-12 items-center justify-center
                                               rounded-2xl border border-emerald-300/20
                                               bg-emerald-400/10 text-emerald-200">

                                    <span
                                        class="material-symbols-outlined text-2xl">
                                        receipt_long
                                    </span>

                                </div>

                                <div>

                                    <p
                                        class="text-xs uppercase tracking-[0.18em]
                                                   text-blue-100/45">
                                        Billing Profile
                                    </p>

                                    <h2
                                        class="mt-1 text-xl font-bold text-white">
                                        {{ $whmcsAccount->email }}
                                    </h2>

                                </div>

                            </div>

                            <a
                                href="{{ route('account.link-whmcs') }}"
                                wire:navigate
                                class="text-sm font-semibold text-cyan-200 hover:text-white">
                                Manage
                            </a>

                        </div>

                        @if (count($whmcsStats))

                        <div
                            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

                            <div
                                class="rounded-2xl border border-white/10
                                               bg-white/5 p-4">

                                <p
                                    class="text-xs text-blue-100/45">
                                    Active Services
                                </p>

                                <p
                                    class="mt-2 text-2xl font-bold text-white">
                                    {{ data_get(
                                                $whmcsStats,
                                                'productsnumactive',
                                                0
                                            ) }}
                                </p>

                            </div>

                            <div
                                class="rounded-2xl border border-white/10
                                               bg-white/5 p-4">

                                <p
                                    class="text-xs text-blue-100/45">
                                    Total Services
                                </p>

                                <p
                                    class="mt-2 text-2xl font-bold text-white">
                                    {{ data_get(
                                                $whmcsStats,
                                                'productsnumtotal',
                                                0
                                            ) }}
                                </p>

                            </div>

                            <div
                                class="rounded-2xl border border-white/10
                                               bg-white/5 p-4">

                                <p
                                    class="text-xs text-blue-100/45">
                                    Unpaid Invoices
                                </p>

                                <p
                                    class="mt-2 text-2xl font-bold text-white">
                                    {{ data_get(
                                                $whmcsStats,
                                                'invoicesunpaid',
                                                0
                                            ) }}
                                </p>

                                <p
                                    class="mt-1 text-xs text-amber-200/80">
                                    Due
                                    {{ $this->formatMoney(
                                                data_get(
                                                    $whmcsStats,
                                                    'invoicesunpaidamount',
                                                    0
                                                )
                                            ) }}
                                </p>

                            </div>

                            <div
                                class="rounded-2xl border border-white/10
                                               bg-white/5 p-4">

                                <p
                                    class="text-xs text-blue-100/45">
                                    Account Credit
                                </p>

                                <p
                                    class="mt-2 text-2xl font-bold text-white">
                                    {{ $this->formatMoney(
                                                data_get(
                                                    $whmcsStats,
                                                    'creditbalance',
                                                    0
                                                )
                                            ) }}
                                </p>

                            </div>

                        </div>

                        @else

                        <div
                            class="mt-4 rounded-2xl border border-amber-300/20
                                           bg-amber-400/10 px-5 py-4
                                           text-sm text-amber-200">
                            Billing summary is currently unavailable.
                            Your account stays linked &mdash; try again later.
                        </div>

                        @endif

                    </div>

                    @endif


                    {{-- Active WHMCS Services --}}

                    @if ($whmcsAccount)

                    <div
                        class="mb-6 rounded-[28px] border border-white/10
                                   bg-white/8 p-6
                                   shadow-[0_16px_50px_rgba(0,0,0,0.18)]
                                   backdrop-blur-2xl">

                        <div
                            class="mb-4 flex items-center justify-between gap-4">

                            <div
                                class="flex items-center gap-4">

                                <div
                                    class="flex h-12 w-12 items-center justify-center
                                               rounded-2xl border border-blue-300/20
                                               bg-blue-400/10 text-blue-200">

                                    <span
                                        class="material-symbols-outlined text-2xl">
                                        dns
                                    </span>

                                </div>

                                <div>

                                    <p
                                        class="text-xs uppercase tracking-[0.18em]
                                                   text-blue-100/45">
                                        Active Services
                                    </p>

                                    <h2
                                        class="mt-1 text-xl font-bold text-white">
                                        {{ count($whmcsActiveServices) }}
                                        Active
                                    </h2>

                                </div>

                            </div>

                            <a
                                href="{{ route('account.services') }}"
                                wire:navigate
                                class="text-sm font-semibold text-cyan-200 hover:text-white">
                                View all
                            </a>

                        </div>

                        @if (count($whmcsActiveServices))

                        <div
                            class="space-y-3">

                            @foreach (
                            $whmcsActiveServices
                            as $service
                            )

                            @php
                            $serviceId = data_get(
                            $service,
                            'id'
                            );

                            $serviceName =
                            data_get(
                            $service,
                            'translated_name'
                            )
                            ?: data_get(
                            $service,
                            'name'
                            )
                            ?: 'Service';

                            $groupName =
                            data_get(
                            $service,
                            'translated_groupname'
                            )
                            ?: data_get(
                            $service,
                            'groupname'
                            )
                            ?: '';

                            $domain =
                            data_get(
                            $service,
                            'domain'
                            );

                            $billingCycle =
                            data_get(
                            $service,
                            'billingcycle'
                            );

                            $nextDueDate =
                            data_get(
                            $service,
                            'nextduedate'
                            );

                            $recurringAmount =
                            data_get(
                            $service,
                            'recurringamount'
                            );

                            $status =
                            data_get(
                            $service,
                            'status',
                            'Active'
                            );
                            @endphp

                            <div
                                wire:key="dashboard-whmcs-service-{{ $serviceId ?? $loop->index }}"
                                class="rounded-2xl border border-white/10
                                                   bg-white/6 p-4">

                                <div
                                    class="flex items-start justify-between gap-4">

                                    <div
                                        class="min-w-0">

                                        @if ($groupName)

                                        <p
                                            class="mb-1 text-xs font-medium
                                                                   text-blue-100/45">
                                            {{ $groupName }}
                                        </p>

                                        @endif

                                        <p
                                            class="truncate text-base
                                                               font-semibold text-white">
                                            {{ $serviceName }}
                                        </p>

                                        @if ($domain)

                                        <p
                                            class="mt-1 truncate text-sm
                                                                   text-cyan-200/80">
                                            {{ $domain }}
                                        </p>

                                        @endif

                                    </div>

                                    <span
                                        class="{{ $this->serviceStatusClass($status) }}">
                                        {{ ucfirst(
                                                        strtolower(
                                                            (string) $status
                                                        )
                                                    ) }}
                                    </span>

                                </div>

                                <div
                                    class="mt-4 grid grid-cols-2 gap-4
                                                       border-t border-white/10 pt-4
                                                       lg:grid-cols-4">

                                    <div>

                                        <p
                                            class="text-xs text-blue-100/45">
                                            Billing Cycle
                                        </p>

                                        <p
                                            class="mt-1 text-sm font-medium text-white">
                                            {{ $billingCycle
                                                            ? ucfirst(
                                                                (string) $billingCycle
                                                            )
                                                            : 'N/A'
                                                        }}
                                        </p>

                                    </div>

                                    <div>

                                        <p
                                            class="text-xs text-blue-100/45">
                                            Recurring Amount
                                        </p>

                                        <p
                                            class="mt-1 text-sm font-medium text-white">
                                            {{ $this->formatMoney(
                                                            $recurringAmount
                                                        ) }}
                                        </p>

                                    </div>

                                    <div>

                                        <p
                                            class="text-xs text-blue-100/45">
                                            Next Due
                                        </p>

                                        <p
                                            class="mt-1 text-sm font-medium text-white">
                                            {{ $nextDueDate
                                                            ? $this->formatDate(
                                                                $nextDueDate
                                                            )
                                                            : 'N/A'
                                                        }}
                                        </p>

                                    </div>

                                    <div>

                                        <p
                                            class="text-xs text-blue-100/45">
                                            Service ID
                                        </p>

                                        <p
                                            class="mt-1 text-sm font-medium text-white">
                                            {{ $serviceId
                                                            ? '#'.$serviceId
                                                            : 'N/A'
                                                        }}
                                        </p>

                                    </div>

                                </div>

                            </div>

                            @endforeach

                        </div>

                        @else

                        <div
                            class="rounded-2xl border border-white/10
                                           bg-white/5 p-6 text-center">

                            <p
                                class="font-semibold text-white">
                                No active services found
                            </p>

                            <p
                                class="mt-1 text-sm text-blue-100/55">
                                We couldn't find any active products
                                on your connected billing account.
                            </p>

                        </div>

                        @endif

                    </div>

                    @endif


                    {{-- Important Info --}}

                    @if (
                    $sentProposalCount > 0
                    || $openTickets > 0
                    )

                    <div
                        class="mb-6 rounded-2xl border border-amber-300/20
                                   bg-amber-400/10 p-4">

                        <div
                            class="flex flex-col gap-3
                                       sm:flex-row sm:items-center sm:justify-between">

                            <p
                                class="text-sm text-blue-100/70">

                                @if ($sentProposalCount > 0)

                                You have
                                {{ $sentProposalCount }}
                                proposal{{ $sentProposalCount > 1 ? 's' : '' }}
                                waiting for review.

                                @endif

                                @if ($openTickets > 0)

                                {{ $sentProposalCount > 0
                                            ? ' Also,'
                                            : 'You'
                                        }}
                                have
                                {{ $openTickets }}
                                open support
                                ticket{{ $openTickets > 1 ? 's' : '' }}.

                                @endif

                            </p>

                            <div
                                class="flex flex-wrap gap-2">

                                @if ($sentProposalCount > 0)

                                <a
                                    href="{{ route('client.proposals.index') }}"
                                    wire:navigate
                                    class="rounded-xl bg-amber-500
                                                   px-4 py-2 text-sm font-semibold
                                                   text-white transition
                                                   hover:bg-amber-600">
                                    View Proposals
                                </a>

                                @endif

                                @if ($openTickets > 0)

                                <a
                                    href="{{ route('client.tickets.index') }}"
                                    wire:navigate
                                    class="rounded-xl bg-white/10
                                                   px-4 py-2 text-sm font-semibold
                                                   text-white transition
                                                   hover:bg-white/15">
                                    View Tickets
                                </a>

                                @endif

                            </div>

                        </div>

                    </div>

                    @endif


                    {{-- Orders + Tickets --}}

                    <div
                        class="grid gap-6 xl:grid-cols-2">

                        {{-- Recent Orders --}}

                        <div
                            class="client-card p-6">

                            <div
                                class="mb-4 flex items-center justify-between gap-4">

                                <h2
                                    class="text-xl font-bold text-white">
                                    Recent Orders
                                </h2>

                                <a
                                    href="{{ route('account.services') }}"
                                    wire:navigate
                                    class="text-sm font-semibold
                                           text-cyan-200 hover:text-white">
                                    View all
                                </a>

                            </div>

                            <div
                                class="space-y-3">

                                @forelse (
                                $recentOrders
                                as $order
                                )

                                @php
                                $orderLabel =
                                $order->plan_name
                                ?? $order->service?->card_title
                                ?? $order->service?->detail_title
                                ?? $order->service?->name
                                ?? 'Order #'.$order->order_no;
                                @endphp

                                <div
                                    wire:key="dashboard-order-{{ $order->id }}"
                                    class="flex items-start justify-between
                                               gap-4 rounded-2xl
                                               border border-white/10
                                               bg-white/6 p-4">

                                    <div
                                        class="min-w-0">

                                        <p
                                            class="truncate font-semibold text-white">
                                            {{ $orderLabel }}
                                        </p>

                                        <p
                                            class="mt-1 text-xs text-blue-100/45">
                                            {{ $order->order_no }}
                                            &middot;
                                            Updated
                                            {{ $this->timeAgo(
                                                    $order->updated_at
                                                ) }}
                                        </p>

                                    </div>

                                    <span
                                        class="{{ $this->orderStatusClass(
                                                $order->status
                                            ) }}">
                                        {{ ucfirst(
                                                str_replace(
                                                    '_',
                                                    ' ',
                                                    $order->status
                                                )
                                            ) }}
                                    </span>

                                </div>

                                @empty

                                <div
                                    class="rounded-2xl border border-dashed
                                               border-white/15 bg-white/5
                                               p-8 text-center">

                                    <p
                                        class="font-semibold text-white">
                                        No orders yet
                                    </p>

                                    <p
                                        class="mt-1 text-sm text-blue-100/50">
                                        Your service orders will appear here.
                                    </p>

                                </div>

                                @endforelse

                            </div>

                        </div>


                        {{-- Latest Tickets --}}

                        <div
                            class="client-card p-6">

                            <div
                                class="mb-4 flex items-center justify-between gap-4">

                                <h2
                                    class="text-xl font-bold text-white">
                                    Latest Tickets
                                </h2>

                                <a
                                    href="{{ route('client.tickets.index') }}"
                                    wire:navigate
                                    class="text-sm font-semibold
                                           text-cyan-200 hover:text-white">
                                    View all
                                </a>

                            </div>

                            <div
                                class="space-y-3">

                                @forelse (
                                $latestTickets
                                as $ticket
                                )

                                <a
                                    href="{{ route(
                                            'client.tickets.index',
                                            [
                                                'ticket' =>
                                                    $ticket->id
                                            ]
                                        ) }}"
                                    wire:navigate
                                    wire:key="dashboard-ticket-{{ $ticket->id }}"
                                    class="block rounded-2xl
                                               border border-white/10
                                               bg-white/6 p-4
                                               transition hover:bg-white/10">

                                    <div
                                        class="flex items-start
                                                   justify-between gap-4">

                                        <div
                                            class="min-w-0">

                                            <p
                                                class="truncate font-semibold text-white">
                                                {{ $ticket->subject
                                                        ?? $ticket->title
                                                        ?? 'Support Ticket'
                                                    }}
                                            </p>

                                            <p
                                                class="mt-1 text-xs text-blue-100/45">
                                                {{ $ticket->ticket_no
                                                        ?? 'Ticket'
                                                    }}
                                                &middot;
                                                {{ $this->timeAgo(
                                                        $ticket->updated_at
                                                    ) }}
                                            </p>

                                        </div>

                                        <span
                                            class="{{ $this->ticketStatusClass(
                                                    $ticket->status
                                                ) }}">
                                            {{ ucfirst(
                                                    str_replace(
                                                        '_',
                                                        ' ',
                                                        $ticket->status
                                                            ?? 'open'
                                                    )
                                                ) }}
                                        </span>

                                    </div>

                                </a>

                                @empty

                                <div
                                    class="rounded-2xl border border-dashed
                                               border-white/15 bg-white/5
                                               p-8 text-center">

                                    <p
                                        class="font-semibold text-white">
                                        No tickets yet
                                    </p>

                                    <p
                                        class="mt-1 text-sm text-blue-100/50">
                                        Support updates will appear here.
                                    </p>

                                </div>

                                @endforelse

                            </div>

                        </div>

                    </div>


                    {{-- Active Subscriptions --}}

                    <div
                        class="mt-6 client-card p-6">

                        <div
                            class="mb-4 flex items-center justify-between gap-4">

                            <h2
                                class="text-xl font-bold text-white">
                                Active Subscriptions
                            </h2>

                            <a
                                href="{{ route('account.tool-subscriptions') }}"
                                wire:navigate
                                class="text-sm font-semibold
                                       text-cyan-200 hover:text-white">
                                View all
                            </a>

                        </div>

                        @if ($activeSubscriptions->isNotEmpty())

                        <div
                            class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">

                            @foreach (
                            $activeSubscriptions
                            as $sub
                            )

                            <div
                                wire:key="dashboard-sub-{{ $sub->id }}"
                                class="flex items-center gap-3
                                               rounded-2xl
                                               border border-white/10
                                               bg-white/6 p-4">

                                <div
                                    class="flex h-11 w-11 shrink-0
                                                   items-center justify-center
                                                   rounded-xl bg-cyan-400/10
                                                   text-cyan-300">

                                    <span
                                        class="material-symbols-outlined">
                                        {{ $sub->toolCategory?->icon
                                                    ?: 'build'
                                                }}
                                    </span>

                                </div>

                                <div
                                    class="min-w-0 flex-1">

                                    <p
                                        class="truncate font-semibold text-white">
                                        {{ $sub->toolCategory?->name
                                                    ?? 'Tool'
                                                }}
                                    </p>

                                    <p
                                        class="mt-0.5 text-xs text-blue-100/45">
                                        {{ $sub->toolPlan?->name
                                                    ?? 'No plan'
                                                }}

                                        @if ($sub->expires_at)

                                        &middot;
                                        Expires
                                        {{ $sub->expires_at->format(
                                                        'd M Y'
                                                    ) }}

                                        @endif
                                    </p>

                                </div>

                            </div>

                            @endforeach

                        </div>

                        @else

                        <div
                            class="rounded-2xl border border-dashed
                                       border-white/15 bg-white/5
                                       p-8 text-center">

                            <p
                                class="font-semibold text-white">
                                No active subscriptions
                            </p>

                            <p
                                class="mt-1 text-sm text-blue-100/50">
                                Your active tool plans will appear here.
                            </p>

                        </div>

                        @endif

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>