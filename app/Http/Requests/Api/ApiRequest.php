<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class ApiRequest extends FormRequest
{
    /**
     * Always treat this request as an API request (JSON).
     * Helps when Accept header isn't set correctly.
     */
    public function expectsJson(): bool
    {
        return true;
    }

    /**
     * Return JSON validation errors.
     */
    protected function failedValidation(Validator $validator): void
    {
        $errorsBag = $validator->errors();
        $errorsArray = $errorsBag->toArray();
        $first = null;
        foreach ($errorsArray as $field => $msgs) {
            if (is_array($msgs) && isset($msgs[0]) && is_string($msgs[0]) && $msgs[0] !== '') {
                $first = $msgs[0];
                break;
            }
        }
        $response = response()->json([
            'message' => $first ?: 'Validation failed.',
            'errors' => $errorsBag,
        ], 422);

        throw new ValidationException($validator, $response);
    }

    /**
     * Return JSON when authorize() fails (403).
     */
    protected function failedAuthorization(): void
    {
        throw new HttpException(403, 'This action is unauthorized.');
    }
}
