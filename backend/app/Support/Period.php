<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;

/**
 * Resolves a dashboard period into inclusive from/to dates and a chart granularity (BR-12, BR-13).
 */
final class Period
{
    public const THIS_MONTH = 'this_month';

    public const LAST_MONTH = 'last_month';

    public const CUSTOM = 'custom';

    public const KEYS = [self::THIS_MONTH, self::LAST_MONTH, self::CUSTOM];

    /** BR-12: a custom period spans at most this many days, both bounds included. */
    public const MAX_DAYS = 366;

    /** BR-13: periods up to this many days are charted per day, longer ones per month. */
    public const MAX_DAILY_DAYS = 31;

    private function __construct(
        public readonly string $key,
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $to,
    ) {}

    public static function resolve(?string $key, ?string $from = null, ?string $to = null): self
    {
        $today = CarbonImmutable::today();

        return match ($key ?? self::THIS_MONTH) {
            self::THIS_MONTH => new self(self::THIS_MONTH, $today->startOfMonth(), $today->endOfMonth()->startOfDay()),
            self::LAST_MONTH => new self(
                self::LAST_MONTH,
                $today->subMonthNoOverflow()->startOfMonth(),
                $today->subMonthNoOverflow()->endOfMonth()->startOfDay(),
            ),
            self::CUSTOM => new self(self::CUSTOM, CarbonImmutable::parse($from), CarbonImmutable::parse($to)),
        };
    }

    /**
     * Number of days covered, both bounds inclusive.
     */
    public static function daysBetween(CarbonImmutable $from, CarbonImmutable $to): int
    {
        return (int) $from->startOfDay()->diffInDays($to->startOfDay()) + 1;
    }

    public function days(): int
    {
        return self::daysBetween($this->from, $this->to);
    }

    public function granularity(): string
    {
        return $this->days() <= self::MAX_DAILY_DAYS ? 'day' : 'month';
    }

    /**
     * Every bucket key between from and to: Y-m-d per day, or Y-m per month.
     *
     * @return list<string>
     */
    public function buckets(): array
    {
        if ($this->granularity() === 'day') {
            $period = CarbonPeriod::create($this->from, '1 day', $this->to);
            $format = 'Y-m-d';
        } else {
            $period = CarbonPeriod::create($this->from->startOfMonth(), '1 month', $this->to->startOfMonth());
            $format = 'Y-m';
        }

        $buckets = [];

        foreach ($period as $date) {
            $buckets[] = $date->format($format);
        }

        return $buckets;
    }

    /**
     * @return array{key: string, from: string, to: string}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'from' => $this->from->toDateString(),
            'to' => $this->to->toDateString(),
        ];
    }
}
