<?php

namespace App\Http\Requests;

use App\Models\JenisLayanan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanLayananRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'kode' => strtoupper(trim((string) $this->input('kode'))),
            'aktif' => $this->boolean('aktif'),
            'pernyataan' => $this->boolean('pernyataan'),
            'butuh_pengantar_rt' => $this->boolean('butuh_pengantar_rt'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $jenis = $this->route('jenisLayanan');
        $id = $jenis instanceof JenisLayanan ? $jenis->id : null;

        return [
            'nama' => ['required', 'string', 'max:150'],
            'kode' => ['required', 'regex:/^[A-Z0-9]{2,10}$/', Rule::unique('jenis_layanan', 'kode')->ignore($id)],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'estimasi_hari' => ['required', 'integer', 'min:1', 'max:30'],
            'aktif' => ['boolean'],
            'butuh_pengantar_rt' => ['boolean'],
            'mode_pengantar' => ['nullable', Rule::in(['unggah', 'verifikasi_online'])],
            'pernyataan' => ['boolean'],
            'template_word' => ['nullable', 'file', 'extensions:docx', 'max:5120', Rule::requiredIf(fn () => $this->boolean('pernyataan') && ! ($jenis instanceof JenisLayanan && filled($jenis->berkas_template)))],
            'naskah' => ['nullable', 'string', 'max:8000', Rule::requiredIf(fn () => $id === null && ! $this->boolean('pernyataan'))],
            'syarat' => ['nullable', 'array', 'max:8'],
            'syarat.*.nama' => ['nullable', 'regex:/^[A-Za-z0-9_]{0,30}$/'],
            'syarat.*.label' => ['nullable', 'string', 'max:80'],
            'field' => ['nullable', 'array', 'max:12'],
            'field.*.nama' => ['nullable', 'regex:/^[A-Za-z0-9_]{0,30}$/'],
            'field.*.label' => ['nullable', 'string', 'max:80'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'naskah.required' => 'Naskah surat wajib diisi.',
            'template_word.required' => 'Unggah berkas Word surat pernyataan.',
            'template_word.extensions' => 'Template harus berupa berkas Word (.docx).',
            'template_word.max' => 'Template terlalu besar. Batasnya 5 MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nama' => 'nama layanan',
            'kode' => 'kode',
            'estimasi_hari' => 'perkiraan hari',
            'naskah' => 'naskah surat',
            'template_word' => 'berkas template',
        ];
    }
}
