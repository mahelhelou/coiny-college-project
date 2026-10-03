<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use App\Support\Money;
use App\Support\Period;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Computes every dashboard figure live from the transactions table (BR-11, BR-17).
 * All sums, counts and groupings run in SQL; PHP only formats and zero-fills.
 */
class DashboardService
{
    public const RECENT_LIMIT = 5;

    private const INCOME_SUM = "COALESCE(SUM(CASE WHEN transactions.type = 'income' THEN transactions.amount ELSE 0 END), 0)";

    private const EXPENSE_SUM = "COALESCE(SUM(CASE WHEN transactions.type = 'expense' THEN transactions.amount ELSE 0 END), 0)";

    private const BALANCE_SUM = "COALESCE(SUM(CASE WHEN transactions.type = 'income' THEN transactions.amount ELSE -transactions.amount END), 0)";

    /**
     * @return array<string, mixed>
     */
    public function summary(User $user, Period $period): array
    {
        return [
            'period' => $period->toArray(),
            'totals' => $this->totals($user, $period),
            'all_time_balance' => $this->allTimeBalance($user),
            'expense_by_category' => $this->expenseByCategory($user, $period),
            'income_vs_expenses' => $this->incomeVsExpenses($user, $period),
            'recent_transactions' => $this->recentTransactions($user, $period),
        ];
    }

    /**
     * @return array{income: string, expenses: string, balance: string, transactions_count: int}
     */
    private function totals(User $user, Period $period): array
    {
        $row = $this->inPeriod($user, $period)
            ->toBase()
            ->selectRaw(self::INCOME_SUM.' AS income')
            ->selectRaw(self::EXPENSE_SUM.' AS expenses')
            ->selectRaw(self::BALANCE_SUM.' AS balance')
            ->selectRaw('COUNT(*) AS transactions_count')
            ->first();

        return [
            'income' => Money::format($row->income),
            'expenses' => Money::format($row->expenses),
            'balance' => Money::format($row->balance),
            'transactions_count' => (int) $row->transactions_count,
        ];
    }

    private function allTimeBalance(User $user): string
    {
        $balance = $user->transactions()->toBase()->selectRaw(self::BALANCE_SUM.' AS balance')->value('balance');

        return Money::format($balance);
    }

    /**
     * @return list<array{category_id: int, name: string, total: string, percentage: float}>
     */
    private function expenseByCategory(User $user, Period $period): array
    {
        return $this->inPeriod($user, $period)
            ->toBase()
            ->join('categories', 'categories.id', '=', 'transactions.category_id')
            ->where('transactions.type', 'expense')
            ->groupBy('categories.id', 'categories.name')
            ->select('categories.id AS category_id', 'categories.name')
            ->selectRaw('SUM(transactions.amount) AS total')
            ->selectRaw('ROUND(SUM(transactions.amount) * 100.0 / SUM(SUM(transactions.amount)) OVER (), 1) AS percentage')
            ->orderByDesc('total')
            ->orderBy('categories.name')
            ->get()
            ->map(fn (object $row) => [
                'category_id' => (int) $row->category_id,
                'name' => $row->name,
                'total' => Money::format($row->total),
                'percentage' => (float) $row->percentage,
            ])
            ->all();
    }

    /**
     * BR-13: one point per bucket between from and to, zero-filled when empty.
     *
     * @return array{granularity: string, points: list<array{bucket: string, income: string, expenses: string}>}
     */
    private function incomeVsExpenses(User $user, Period $period): array
    {
        $granularity = $period->granularity();

        $rows = $this->inPeriod($user, $period)
            ->toBase()
            ->selectRaw($this->bucketExpression($granularity).' AS bucket')
            ->selectRaw(self::INCOME_SUM.' AS income')
            ->selectRaw(self::EXPENSE_SUM.' AS expenses')
            ->groupBy('bucket')
            ->get()
            ->keyBy('bucket');

        $points = array_map(fn (string $bucket) => [
            'bucket' => $bucket,
            'income' => Money::format($rows[$bucket]->income ?? 0),
            'expenses' => Money::format($rows[$bucket]->expenses ?? 0),
        ], $period->buckets());

        return ['granularity' => $granularity, 'points' => $points];
    }

    /**
     * @return Collection<int, Transaction>
     */
    private function recentTransactions(User $user, Period $period): Collection
    {
        return $this->inPeriod($user, $period)
            ->with('category')
            ->latestFirst()
            ->limit(self::RECENT_LIMIT)
            ->get();
    }

    /**
     * BR-12 + BR-18: the user's transactions within the period, both bounds inclusive.
     *
     * @return Builder<Transaction>
     */
    private function inPeriod(User $user, Period $period): Builder
    {
        return $user->transactions()
            ->getQuery()
            ->whereBetween('transactions.transaction_date', [$period->from->toDateString(), $period->to->toDateString()]);
    }

    private function bucketExpression(string $granularity): string
    {
        if ($granularity === 'day') {
            return 'transactions.transaction_date';
        }

        return DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', transactions.transaction_date)"
            : "DATE_FORMAT(transactions.transaction_date, '%Y-%m')";
    }
}
