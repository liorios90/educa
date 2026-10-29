<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\EstablecimientoTarea;
use App\Services\LibretaFamiliar;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreTareaEntregaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $tarea = $this->route('tarea');

        if ($user === null || ! $user->hasRole(Role::Alumno) || ! $tarea instanceof EstablecimientoTarea) {
            return false;
        }

        $alumno = $this->container->make(LibretaFamiliar::class)->alumnosDe($user)->first();

        if ($alumno === null || ! $tarea->destinatarios()->where('alumno_id', $alumno->id)->exists()) {
            abort(404);
        }

        return true;
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'mensaje' => ['nullable', 'string', 'max:5000'],
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
            'archivos.max' => 'Puedes adjuntar hasta 5 archivos.',
            'archivos.*.mimes' => 'El archivo no tiene un formato permitido. Usa PDF, Word, Excel, PowerPoint, imagen o texto.',
            'archivos.*.max' => 'Cada archivo puede pesar hasta 10 MB.',
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $mensaje = trim((string) $this->input('mensaje'));
                $archivos = $this->file('archivos', []);

                if ($mensaje === '' && $archivos === []) {
                    $validator->errors()->add('archivos', 'Escribe un texto o adjunta al menos un documento.');
                }
            },
        ];
    }

    protected function getRedirectUrl(): string
    {
        $tarea = $this->route('tarea');
        $mes = now()->format('Y-m');
        $tareaId = null;

        if ($tarea instanceof EstablecimientoTarea) {
            $mes = $tarea->fecha_inicio->format('Y-m');
            $tareaId = $tarea->id;
        }

        return route('alumno.tareas.index', array_filter([
            'mes' => $mes,
            'tarea' => $tareaId,
        ]));
    }
}
