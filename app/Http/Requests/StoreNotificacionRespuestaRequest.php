<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreNotificacionRespuestaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cuerpo' => ['required', 'string', 'max:5000'],
            'archivos' => ['nullable', 'array', 'max:5'],
            'archivos.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,ppt,pptx,odt,ods,txt', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cuerpo.required' => 'Escribe la respuesta.',
            'archivos.max' => 'Puedes adjuntar hasta 5 archivos.',
            'archivos.*.mimes' => 'El archivo no tiene un formato permitido. Usa PDF, Word, Excel, PowerPoint, imagen o texto.',
            'archivos.*.max' => 'Cada archivo puede pesar hasta 10 MB.',
        ];
    }
}
