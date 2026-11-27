<?php

namespace App\Http\Requests;

use App\Enums\Peran;
use App\Services\Wilayah;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanPenggunaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'peran' => ['required', Rule::in([
                Peran::Operator->value,
                Peran::Lurah->value,
                Peran::Admin->value,
                Peran::RtRw->value,
            ])],
            'no_hp' => ['nullable', 'regex:/^08\d{8,12}$/'],
            'dukuh' => ['required_if:peran,rt_rw', 'nullable', 'string', 'max:100'],
            'rt' => ['required_if:peran,rt_rw', 'nullable', 'regex:/^\d{1,3}$/'],
            'rw' => ['required_if:peran,rt_rw', 'nullable', 'regex:/^\d{1,3}$/'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    protected function passedValidation(): void
    {
        if ($this->input('peran') !== Peran::RtRw->value) {
            return;
        }

        $this->merge([
            'rt' => Wilayah::kode($this->input('rt')),
            'rw' => Wilayah::kode($this->input('rw')),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'password' => 'kata sandi',
            'peran' => 'peran',
        ];
    }
}
