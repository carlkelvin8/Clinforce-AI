<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class EmployerProfileUpsertRequest extends ApiRequest
{
    public function authorize(): bool
    {
        $u = $this->user();
        return (bool)$u && in_array($u->role, ['employer','admin'], true);
    }

    public function rules(): array
    {
        return [
            'business_name' => ['required','string','min:2','max:190'],
            'business_type' => ['required', Rule::in(['clinic','hospital','medical_agency','other'])],
            'country' => ['nullable','string','max:200'],
            'state' => ['nullable','string','min:2','max:120'],
            'city' => ['nullable','string','min:2','max:120'],
            'zip_code' => ['nullable','string','min:2','max:20'],
            'tax_id' => ['nullable','string','min:2','max:50'],
            'address_line' => ['nullable','string','max:255'],
            'website_url' => ['nullable','url','max:255'],
            'data_retention_days' => ['nullable','integer','min:7','max:3650'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['business_name','state','city','zip_code','tax_id','address_line','website_url'] as $k) {
            if ($this->has($k)) {
                $val = trim((string)$this->input($k));
                $this->merge([$k => $val === '' ? null : $val]);
            }
        }
        if ($this->has('country')) {
            $val = trim((string)$this->input('country'));
            $this->merge(['country' => $val === '' ? null : $val]);
        }
    }
}
