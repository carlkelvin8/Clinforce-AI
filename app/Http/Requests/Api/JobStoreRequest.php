<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class JobStoreRequest extends ApiRequest
{
    public function authorize(): bool
    {
        $u = $this->user();
        return (bool)$u && in_array($u->role, ['employer','agency','admin'], true);
    }

    public function rules(): array
    {
        return [
            'title' => ['required','string','min:5','max:190'],
            'description' => ['required','string','min:30','max:20000'],
            'employment_type' => ['required', Rule::in(['full_time','part_time','contract','temporary','internship','full_time_part_time','contract_temporary'])],
            'work_mode' => ['required', Rule::in(['on_site','remote','hybrid'])],
            'country' => ['nullable','string','max:200'],
            'city' => ['nullable','string','min:2','max:120'],
            'salary_min' => ['nullable','numeric','min:0'],
            'salary_max' => ['nullable','numeric','min:0','gte:salary_min'],
            'salary_type' => ['nullable', Rule::in(['annually','hourly'])],
            'salary_currency' => ['nullable','string','size:3'],
            'closes_at'       => ['nullable','date','after:today'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('country')) {
            $this->merge(['country' => trim((string) $this->input('country'))]);
        }
        if ($this->has('city')) {
            $this->merge(['city' => trim((string) $this->input('city'))]);
        }
        if ($this->has('title')) {
            $this->merge(['title' => trim((string) $this->input('title'))]);
        }
    }
}
