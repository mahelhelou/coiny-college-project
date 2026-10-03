<?php

namespace App\Http\Requests;

use App\Enums\TransactionType;
use App\Models\Category;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Used for create and rename. On rename the type is taken from the stored category (BR-04).
 */
class CategoryRequest extends FormRequest
{
    private ?Category $existing = null;

    /**
     * BR-18: a category the user does not own is a 404, before any validation runs.
     */
    protected function prepareForValidation(): void
    {
        if ($this->isUpdate()) {
            $this->existing = $this->user()->categories()->findOrFail($this->route('category'));
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:50', $this->uniqueNameRule()],
        ];

        if (! $this->isUpdate()) {
            $rules['type'] = ['required', Rule::enum(TransactionType::class)];
        }

        return $rules;
    }

    /**
     * BR-06: unique per (user, type), case-insensitive and trimmed.
     */
    private function uniqueNameRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            $type = $this->isUpdate() ? $this->existing->type : TransactionType::tryFrom((string) $this->input('type'));

            if (! is_string($value) || $type === null) {
                return;
            }

            $taken = $this->user()->categories()
                ->where('type', $type)
                ->named($value)
                ->when($this->isUpdate(), fn ($q) => $q->whereKeyNot($this->existing->getKey()))
                ->exists();

            if ($taken) {
                $fail("You already have a {$type->value} category with this name.");
            }
        };
    }

    private function isUpdate(): bool
    {
        return $this->isMethod('PUT') || $this->isMethod('PATCH');
    }
}
