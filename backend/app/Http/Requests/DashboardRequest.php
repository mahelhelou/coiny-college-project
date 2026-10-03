<?php

namespace App\Http\Requests;

use App\Support\Period;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class DashboardRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'period' => ['nullable', Rule::in(Period::KEYS)],
            'from' => ['required_if:period,'.Period::CUSTOM, 'nullable', 'date_format:Y-m-d'],
            'to' => ['required_if:period,'.Period::CUSTOM, 'nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }

    /**
     * BR-12: a custom period may not exceed 366 days.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty() || $this->input('period') !== Period::CUSTOM) {
                    return;
                }

                $days = Period::daysBetween(
                    CarbonImmutable::parse($this->input('from')),
                    CarbonImmutable::parse($this->input('to')),
                );

                if ($days > Period::MAX_DAYS) {
                    $validator->errors()->add('to', 'The period may not be longer than '.Period::MAX_DAYS.' days.');
                }
            },
        ];
    }

    public function period(): Period
    {
        return Period::resolve($this->validated('period'), $this->validated('from'), $this->validated('to'));
    }
}
