<?php

namespace App\Models;

use App\Enums\TransactionType;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable(['category_id', 'type', 'amount', 'transaction_date', 'description'])]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount' => 'decimal:2',
            'transaction_date' => 'date:Y-m-d',
        ];
    }

    /**
     * Store a plain Y-m-d (BR-09) so date comparisons never depend on a time part.
     */
    protected function transactionDate(): Attribute
    {
        return Attribute::set(fn (mixed $value) => Carbon::parse($value)->toDateString());
    }

    /**
     * Apply the list filters (TRX-08/09/10). All filters combine with AND.
     *
     * @param  Builder<Transaction>  $query
     * @param  array<string, mixed>  $filters
     */
    public function scopeFilter(Builder $query, array $filters): void
    {
        $query
            ->when($filters['type'] ?? null, fn (Builder $q, string $type) => $q->where('type', $type))
            ->when($filters['category_id'] ?? null, fn (Builder $q, int|string $id) => $q->where('category_id', $id))
            ->when($filters['date_from'] ?? null, fn (Builder $q, string $from) => $q->where('transaction_date', '>=', $from))
            ->when($filters['date_to'] ?? null, fn (Builder $q, string $to) => $q->where('transaction_date', '<=', $to))
            ->when($filters['search'] ?? null, function (Builder $q, string $search) {
                $escaped = addcslashes($search, '\\%_');
                $q->where('description', 'like', "%{$escaped}%");
            });
    }

    /**
     * BR-16: newest first, stable within a day.
     *
     * @param  Builder<Transaction>  $query
     */
    public function scopeLatestFirst(Builder $query): void
    {
        $query->orderByDesc('transaction_date')->orderByDesc('id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
