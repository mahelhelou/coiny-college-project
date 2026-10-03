<?php

namespace App\Models;

use App\Enums\TransactionType;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'type'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    /**
     * BR-05: categories every new user starts with.
     */
    public const DEFAULTS = [
        'income' => ['Salary', 'Freelance', 'Gift', 'Other Income'],
        'expense' => ['Food', 'Transportation', 'Education', 'Shopping', 'Bills', 'Health', 'Entertainment', 'Other Expense'],
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
        ];
    }

    /**
     * BR-05: call inside the same DB transaction that creates the user.
     */
    public static function createDefaultsFor(User $user): void
    {
        $rows = [];

        foreach (self::DEFAULTS as $type => $names) {
            foreach ($names as $name) {
                $rows[] = ['name' => $name, 'type' => $type];
            }
        }

        $user->categories()->createMany($rows);
    }

    /**
     * BR-06: match a name ignoring case and surrounding whitespace.
     *
     * @param  Builder<Category>  $query
     */
    public function scopeNamed(Builder $query, string $name): void
    {
        $query->whereRaw('LOWER(name) = ?', [Str::lower(trim($name))]);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
