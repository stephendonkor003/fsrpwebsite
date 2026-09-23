<?php

namespace App\Support;

use App\Models\EventRegistration;
use App\Models\EventRegistrationSearchToken;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AdminRegistrationReport
{
    public function __construct(private readonly EventRegistrationSearchIndex $searchIndex) {}

    /**
     * @param  array<string, int|string>  $filters
     * @return Builder<EventRegistration>
     */
    public function query(array $filters): Builder
    {
        $query = EventRegistration::query();

        $query
            ->when(
                isset($filters['event']),
                fn (Builder $builder): Builder => $builder->where('event_slug', $filters['event']),
            )
            ->when(
                ($filters['verification_status'] ?? null) === 'verified',
                fn (Builder $builder): Builder => $builder->whereNotNull('official_email_verified_at'),
            )
            ->when(
                ($filters['verification_status'] ?? null) === 'unverified',
                fn (Builder $builder): Builder => $builder->whereNull('official_email_verified_at'),
            )
            ->when(
                isset($filters['mail_status']),
                fn (Builder $builder): Builder => $builder->where('confirmation_email_status', $filters['mail_status']),
            );

        if (isset($filters['member_state'])) {
            $memberStateHash = $this->searchIndex->exactToken(
                'member_state',
                (string) $filters['member_state'],
            );

            $query->whereHas(
                'searchTokens',
                fn (Builder $tokens): Builder => $tokens
                    ->where('namespace', 'member_state')
                    ->where('token_hash', $memberStateHash),
            );
        }

        [$dateFrom, $dateTo] = $this->dateBounds($filters);

        if ($dateFrom !== null) {
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($dateTo !== null) {
            $query->where('created_at', '<=', $dateTo);
        }

        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $this->applySearch($query, $search);
        }

        return $query;
    }

    /**
     * @param  array<string, int|string>  $filters
     * @return LengthAwarePaginator<int, EventRegistration>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return $this->query($filters)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 25))
            ->withQueryString();
    }

    /**
     * @param  array<string, int|string>  $filters
     * @return array{total: int, today: int, last_7_days: int, verified: int, pending: int, mail_failures: int}
     */
    public function metrics(array $filters): array
    {
        $query = $this->query($filters);

        return [
            'total' => (clone $query)->count(),
            'today' => (clone $query)->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])->count(),
            'last_7_days' => (clone $query)->where('created_at', '>=', now()->subDays(6)->startOfDay())->count(),
            'verified' => (clone $query)->whereNotNull('official_email_verified_at')->count(),
            'pending' => (clone $query)->whereNull('official_email_verified_at')->count(),
            'mail_failures' => (clone $query)->where('confirmation_email_status', EventRegistration::EMAIL_FAILED)->count(),
        ];
    }

    /**
     * @param  array<string, int|string>  $filters
     * @return array<int, array{date: string, label: string, count: int}>
     */
    public function dailyTrend(array $filters, int $maximumDays = 90): array
    {
        [$dateFrom, $dateTo] = $this->chartDateBounds($filters, $maximumDays);
        $query = $this->query($filters)
            ->whereBetween('created_at', [$dateFrom, $dateTo]);
        $counts = $query
            ->selectRaw('DATE(created_at) AS registration_date, COUNT(*) AS aggregate')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('registration_date')
            ->get()
            ->mapWithKeys(
                static fn (EventRegistration $row): array => [
                    (string) $row->getAttribute('registration_date') => (int) $row->getAttribute('aggregate'),
                ],
            );
        $trend = [];

        for ($date = $dateFrom->startOfDay(); $date->lte($dateTo); $date = $date->addDay()) {
            $key = $date->toDateString();
            $trend[] = [
                'date' => $key,
                'label' => $date->format('d M'),
                'count' => (int) $counts->get($key, 0),
            ];
        }

        return $trend;
    }

    /**
     * @param  array<string, int|string>  $filters
     * @return array<int, array{country: string, count: int, percentage: float}>
     */
    public function countryBreakdown(array $filters, int $limit = 12): array
    {
        $countriesByHash = collect(config('seed_summit.member_states', []))
            ->mapWithKeys(fn (string $country): array => [
                $this->searchIndex->exactToken('member_state', $country) => $country,
            ]);

        if ($countriesByHash->isEmpty()) {
            return [];
        }

        $counts = EventRegistrationSearchToken::query()
            ->selectRaw('token_hash, COUNT(*) AS aggregate')
            ->where('namespace', 'member_state')
            ->whereIn('token_hash', $countriesByHash->keys())
            ->whereIn(
                'event_registration_id',
                $this->query($filters)->select('event_registrations.id'),
            )
            ->groupBy('token_hash')
            ->get()
            ->mapWithKeys(static fn (EventRegistrationSearchToken $token): array => [
                $token->token_hash => (int) $token->getAttribute('aggregate'),
            ]);
        $total = max(1, $counts->sum());

        return $counts
            ->map(fn (int $count, string $hash): array => [
                'country' => (string) $countriesByHash->get($hash, 'Unknown'),
                'count' => $count,
                'percentage' => round(($count / $total) * 100, 1),
            ])
            ->sortByDesc('count')
            ->take(max(1, $limit))
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     member_states: array<int, string>,
     *     events: array<int, array{value: string, label: string}>,
     *     verification_statuses: array<string, string>,
     *     mail_statuses: array<string, string>,
     *     per_page_options: array<int, int>
     * }
     */
    public function filterOptions(): array
    {
        $events = EventRegistration::query()
            ->select('event_slug')
            ->selectRaw('MIN(event_title) AS event_title')
            ->groupBy('event_slug')
            ->orderBy('event_title')
            ->get()
            ->map(static fn (EventRegistration $registration): array => [
                'value' => $registration->event_slug,
                'label' => $registration->event_title,
            ])
            ->all();

        return [
            'member_states' => array_values(config('seed_summit.member_states', [])),
            'events' => $events,
            'verification_statuses' => [
                'verified' => 'Verified',
                'unverified' => 'Not verified',
            ],
            'mail_statuses' => [
                EventRegistration::EMAIL_PENDING => 'Pending',
                EventRegistration::EMAIL_QUEUED => 'Queued',
                EventRegistration::EMAIL_SENDING => 'Sending',
                EventRegistration::EMAIL_SENT => 'Sent',
                EventRegistration::EMAIL_FAILED => 'Failed',
            ],
            'per_page_options' => [15, 25, 50, 100],
        ];
    }

    /**
     * @param  array<string, int|string>  $filters
     * @return Collection<int, EventRegistration>
     */
    public function recent(array $filters, int $limit = 6): Collection
    {
        return $this->query($filters)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /** @param array<string, int|string> $filters */
    public function describeFilters(array $filters): string
    {
        $descriptions = [];

        if (isset($filters['event'])) {
            $descriptions[] = 'Event: '.Str::headline((string) $filters['event']);
        }

        if (isset($filters['member_state'])) {
            $descriptions[] = 'Member State: '.$filters['member_state'];
        }

        if (isset($filters['verification_status'])) {
            $descriptions[] = 'Email: '.Str::headline((string) $filters['verification_status']);
        }

        if (isset($filters['mail_status'])) {
            $descriptions[] = 'Acknowledgement: '.Str::headline((string) $filters['mail_status']);
        }

        if (isset($filters['date_from']) || isset($filters['date_to'])) {
            $descriptions[] = sprintf(
                'Submitted: %s to %s',
                $filters['date_from'] ?? 'earliest',
                $filters['date_to'] ?? 'latest',
            );
        } elseif (($filters['period'] ?? 'all') !== 'all') {
            $descriptions[] = 'Period: '.str_replace('_', ' ', (string) $filters['period']);
        }

        if (isset($filters['search'])) {
            $descriptions[] = 'Search applied';
        }

        return $descriptions === [] ? 'All retained registration records' : implode(' | ', $descriptions);
    }

    /**
     * @param  Builder<EventRegistration>  $query
     */
    private function applySearch(Builder $query, string $search): void
    {
        $tokenGroups = $this->searchIndex->queryTokenHashes($search);

        $query->where(function (Builder $matches) use ($search, $tokenGroups): void {
            $matches->whereRaw('1 = 0');

            if (Str::isUuid($search)) {
                $matches->orWhere('public_id', Str::lower($search));
            }

            if (filter_var($search, FILTER_VALIDATE_EMAIL) !== false) {
                $matches->orWhere('official_email_hash', EventRegistration::emailHash($search));
            }

            if ($tokenGroups !== []) {
                $matches->orWhere(function (Builder $indexed) use ($tokenGroups): void {
                    foreach ($tokenGroups as $tokenGroup) {
                        $indexed->whereHas('searchTokens', function (Builder $tokens) use ($tokenGroup): void {
                            $tokens->where(function (Builder $tokenMatch) use ($tokenGroup): void {
                                foreach ($tokenGroup as $namespace => $hash) {
                                    $tokenMatch->orWhere(function (Builder $candidate) use ($namespace, $hash): void {
                                        $candidate
                                            ->where('namespace', $namespace)
                                            ->where('token_hash', $hash);
                                    });
                                }
                            });
                        });
                    }
                });
            }
        });
    }

    /**
     * @param  array<string, int|string>  $filters
     * @return array{0: ?CarbonImmutable, 1: ?CarbonImmutable}
     */
    private function dateBounds(array $filters): array
    {
        if (isset($filters['date_from']) || isset($filters['date_to'])) {
            return [
                isset($filters['date_from'])
                    ? CarbonImmutable::parse((string) $filters['date_from'])->startOfDay()
                    : null,
                isset($filters['date_to'])
                    ? CarbonImmutable::parse((string) $filters['date_to'])->endOfDay()
                    : null,
            ];
        }

        $end = CarbonImmutable::now()->endOfDay();

        return match ($filters['period'] ?? 'all') {
            'today' => [$end->startOfDay(), $end],
            '7_days' => [$end->subDays(6)->startOfDay(), $end],
            '30_days' => [$end->subDays(29)->startOfDay(), $end],
            default => [null, null],
        };
    }

    /**
     * @param  array<string, int|string>  $filters
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function chartDateBounds(array $filters, int $maximumDays): array
    {
        [$dateFrom, $dateTo] = $this->dateBounds($filters);
        $end = ($dateTo ?? CarbonImmutable::now())->endOfDay();
        $start = ($dateFrom ?? $end->subDays(29))->startOfDay();
        $maximumDays = max(1, $maximumDays);

        if ($start->diffInDays($end) >= $maximumDays) {
            $start = $end->subDays($maximumDays - 1)->startOfDay();
        }

        return [$start, $end];
    }
}
