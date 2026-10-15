<?php

namespace App\Support\Admin;

use App\Enums\ProjectStatus;
use App\Models\Invoice;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Visit;
use App\Models\WorkspaceProject;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class DashboardOverview
{
    public const PERIODS = [
        'today' => 'Today',
        '7d' => 'Last 7 Days',
        '30d' => 'Last 30 Days',
        'month' => 'This Month',
        'all' => 'All Time',
    ];

    public function range(string $period): array
    {
        $end = CarbonImmutable::instance(now());
        $start = match ($period) {
            'today' => $end->startOfDay(),
            '7d' => $end->subDays(6)->startOfDay(),
            'month' => $end->startOfMonth(),
            'all' => null,
            default => $end->subDays(29)->startOfDay(),
        };

        return [$start, $end];
    }

    public function applyRange(Builder $query, string $period, string $column = 'created_at', bool $dateOnly = false): Builder
    {
        [$start, $end] = $this->range($period);
        $query->where($column, '<=', $dateOnly ? $end->toDateString() : $end);

        if ($start !== null) {
            $query->where($column, '>=', $dateOnly ? $start->toDateString() : $start);
        }

        return $query;
    }

    public function metrics(string $period = '30d'): array
    {
        $users = $this->applyRange(User::query(), $period);
        $tickets = $this->applyRange(SupportTicket::query(), $period)->where('status', '!=', 'closed');
        $projects = $this->applyRange(WorkspaceProject::query(), $period)
            ->where('is_active', true)
            ->whereNotIn('status', [ProjectStatus::COMPLETED->value, ProjectStatus::CANCELLED->value]);

        // Invoices have no paid_at field; report paid invoices by issue date.
        $revenue = $this->applyRange(Invoice::query(), $period, 'issue_date', dateOnly: true)
            ->where('status', 'paid')
            ->where('currency', config('app.currency_code', 'BDT'))
            ->with('items')
            ->get()
            ->sum(fn (Invoice $invoice) => $invoice->total());

        $userCount = $users->count();

        return [
            'totalUsers' => $userCount,
            'newUsers' => $userCount,
            'activeTickets' => (clone $tickets)->count(),
            'highPriorityTickets' => (clone $tickets)->whereIn('priority', ['high', 'urgent'])->count(),
            'monthlyRevenue' => (float) $revenue,
            'activeProjects' => (clone $projects)->count(),
            'nearDeadlineProjects' => $this->applyRange(clone $projects, $period, 'target_date', dateOnly: true)->count(),
        ];
    }

    public function revenueTrend(string $period, float $currentRevenue): array
    {
        $range = $this->previousRange($period);
        if ($range === null) {
            return $this->noComparison();
        }
        [$start, $end] = $range;
        $previousRevenue = (float) Invoice::query()
            ->where('status', 'paid')
            ->where('currency', config('app.currency_code', 'BDT'))
            ->whereBetween('issue_date', [$start->toDateString(), $end->toDateString()])
            ->with('items')->get()->sum(fn (Invoice $invoice) => $invoice->total());

        return $this->formatTrend($currentRevenue, $previousRevenue, $start, $end);
    }

    public function visitorTrend(string $period, int $currentVisits): array
    {
        $range = $this->previousRange($period);
        if ($range === null) {
            return $this->noComparison();
        }
        [$start, $end] = $range;
        $previousVisits = Visit::query()->whereBetween('created_at', [$start, $end->endOfDay()])->count();

        return $this->formatTrend($currentVisits, $previousVisits, $start, $end);
    }

    private function previousRange(string $period): ?array
    {
        [$start, $end] = $this->range($period);
        if ($start === null) {
            return null;
        }
        if ($period === 'month') {
            $previousStart = $start->subMonthNoOverflow()->startOfMonth();
            $previousEnd = $previousStart->addDays(min($end->day, $previousStart->daysInMonth) - 1);
        } else {
            $days = (int) $start->diffInDays($end->startOfDay()) + 1;
            $previousStart = $start->subDays($days);
            $previousEnd = $start->subDay();
        }

        return [$previousStart, $previousEnd];
    }

    private function noComparison(): array
    {
        return ['percentage' => null, 'label' => '—', 'direction' => 'flat', 'description' => 'No previous period for All Time'];
    }

    private function formatTrend(float $current, float $previous, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $description = 'Compared with '.$start->format('M d, Y').' – '.$end->format('M d, Y');
        if ($previous <= 0 && $current > 0) {
            return ['percentage' => null, 'label' => 'New', 'direction' => 'up', 'description' => $description.' (no previous activity)'];
        }
        $percentage = $previous > 0 ? round(($current - $previous) / $previous * 100, 1) : 0.0;
        $direction = $percentage > 0 ? 'up' : ($percentage < 0 ? 'down' : 'flat');
        $number = rtrim(rtrim(number_format(abs($percentage), 1, '.', ''), '0'), '.');
        $label = ($percentage > 0 ? '+' : ($percentage < 0 ? '-' : '')).$number.'%';

        return ['percentage' => $percentage, 'label' => $label, 'direction' => $direction, 'description' => $description];
    }
}
