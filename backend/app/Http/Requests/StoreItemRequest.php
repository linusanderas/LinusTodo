<?php

namespace App\Http\Requests;

use App\Enums\ItemKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('title'))) {
            $this->merge(['title' => trim($this->input('title'))]);
        }

        if ($this->input('occurs_at') === '') {
            $this->merge(['occurs_at' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'kind' => ['required', Rule::enum(ItemKind::class)],
            'occurs_at' => [
                Rule::requiredIf(fn () => in_array($this->input('kind'), [ItemKind::Meeting->value, ItemKind::Task->value], true)),
                Rule::prohibitedIf(fn () => $this->input('kind') === ItemKind::Todo->value),
                'nullable',
                'date',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Skriv en titel.',
            'title.max' => 'Titeln får vara högst 200 tecken.',
            'kind.required' => 'Välj en typ.',
            'kind.enum' => 'Typen måste vara uppgift, möte eller tidsbunden uppgift.',
            'occurs_at.required' => 'Ange datum och tid.',
            'occurs_at.prohibited' => 'En vanlig uppgift har ingen tid.',
            'occurs_at.date' => 'Datum och tid är ogiltigt.',
        ];
    }
}
