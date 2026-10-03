<?php

namespace App\Http\Requests;

use App\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared by create and full-replace update.
 */
class TransactionRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(TransactionType::class)],
            // BR-01
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:9999999999.99'],
            // BR-02 + BR-03 in one rule
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')
                ->where('user_id', $this->user()->id)
                ->where('type', $this->input('type'))],
            // BR-09
            'transaction_date' => ['required', 'date_format:Y-m-d',
                'after_or_equal:2000-01-01', 'before_or_equal:tomorrow'],
            // BR-10
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.exists' => 'The selected category is invalid or does not match the transaction type.',
            'transaction_date.before_or_equal' => 'The transaction date cannot be in the future.',
        ];
    }
}
