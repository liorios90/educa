<?php

namespace App\Http\Requests;

use App\Auth\DocenteContexto;
use App\Enums\Role;
use App\Enums\TipoContenidoTarea;
use App\Services\AsignacionesDocente;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class StoreTareaRequest extends FormRequest
{
    protected ?DocenteContexto $contexto = null;

    /**
     * @var list<int>
     */
    protected array $alumnoIds = [];

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->hasRole(Role::Docente);
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'establecimiento_aula_id' => ['required', 'integer'],
            'establecimiento_asignatura_id' => ['required', 'integer'],
            'establecimiento_periodo_ciclo_id' => ['required', 'integer'],
            'parcial_numero' => ['required', 'integer', 'min:1'],
            'nombre' => ['required', 'string', 'max:160'],
            'descripcion' => ['required', 'string', 'max:20000'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'toda_el_aula' => ['sometimes', 'boolean'],
            'destinatarios' => ['nullable', 'array'],
            'destinatarios.*' => ['integer'],
            'contenido_tipo' => ['required', Rule::enum(TipoContenidoTarea::class)],
            'contenido_texto' => ['nullable', 'string', 'max:10000'],
            'contenido_url' => ['nullable', 'url', 'max:500'],
            'contenido_archivo' => $this->reglasArchivo(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'establecimiento_aula_id.required' => 'Selecciona un aula.',
            'establecimiento_asignatura_id.required' => 'Selecciona una materia.',
            'establecimiento_periodo_ciclo_id.required' => 'Selecciona un quimestre o trimestre.',
            'parcial_numero.required' => 'Selecciona un parcial.',
            'nombre.required' => 'Escribe el nombre de la tarea.',
            'descripcion.required' => 'Escribe la descripción de la tarea.',
            'fecha_inicio.required' => 'Indica la fecha de inicio.',
            'fecha_fin.required' => 'Indica la fecha de entrega.',
            'fecha_fin.after_or_equal' => 'La fecha de entrega debe ser igual o posterior al inicio.',
            'contenido_tipo.required' => 'Elige el tipo de contenido.',
            'contenido_url.url' => 'Escribe un enlace válido.',
            'contenido_archivo.required' => 'Sube el archivo de la tarea.',
            'contenido_archivo.mimes' => 'El archivo no tiene un formato permitido.',
            'contenido_archivo.max' => 'El archivo supera el tamaño permitido.',
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $user = $this->user();

                if ($user === null) {
                    abort(404);
                }

                $asignaciones = $this->asignaciones();
                $contexto = $asignaciones->contexto(
                    $user,
                    (int) $this->input('establecimiento_aula_id'),
                    (int) $this->input('establecimiento_asignatura_id'),
                    (int) $this->input('establecimiento_periodo_ciclo_id'),
                    (int) $this->input('parcial_numero'),
                );

                if ($contexto === null) {
                    abort(404);
                }

                $this->contexto = $contexto;
                $this->validarContenido($validator);
                $this->validarDestinatarios($validator, $asignaciones, $contexto);
            },
        ];
    }

    public function contexto(): DocenteContexto
    {
        abort_unless($this->contexto instanceof DocenteContexto, 404);

        return $this->contexto;
    }

    /**
     * @return list<int>
     */
    public function alumnoIds(): array
    {
        return $this->alumnoIds;
    }

    public function tipo(): TipoContenidoTarea
    {
        return TipoContenidoTarea::from((string) $this->validated('contenido_tipo'));
    }

    public function archivo(): ?UploadedFile
    {
        $archivo = $this->file('contenido_archivo');

        return $archivo instanceof UploadedFile ? $archivo : null;
    }

    /**
     * @return list<string>
     */
    protected function reglasArchivo(): array
    {
        $tipo = TipoContenidoTarea::tryFrom((string) $this->input('contenido_tipo'));

        if ($tipo === TipoContenidoTarea::Pdf) {
            return ['required', 'file', 'mimes:pdf', 'max:10240'];
        }

        if ($tipo === TipoContenidoTarea::Imagen) {
            return ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'];
        }

        return ['nullable', 'file'];
    }

    protected function validarContenido(Validator $validator): void
    {
        $tipo = $this->tipo();

        if ($tipo === TipoContenidoTarea::Texto && trim((string) $this->input('contenido_texto')) === '') {
            $validator->errors()->add('contenido_texto', 'Escribe el contenido de la tarea.');
        }

        if ($tipo === TipoContenidoTarea::Url && trim((string) $this->input('contenido_url')) === '') {
            $validator->errors()->add('contenido_url', 'Escribe el enlace de la tarea.');
        }
    }

    protected function validarDestinatarios(Validator $validator, AsignacionesDocente $asignaciones, DocenteContexto $contexto): void
    {
        $permitidos = $asignaciones->alumnos($contexto)->pluck('id')->map(fn (mixed $id): int => (int) $id);

        if ($this->boolean('toda_el_aula')) {
            $this->alumnoIds = $permitidos->all();
        } else {
            $this->alumnoIds = collect($this->input('destinatarios', []))
                ->map(fn (mixed $id): int => (int) $id)
                ->unique()
                ->filter(fn (int $id): bool => $permitidos->contains($id))
                ->values()
                ->all();
        }

        if ($this->alumnoIds === []) {
            $validator->errors()->add('destinatarios', 'Selecciona al menos un alumno.');
        }
    }

    protected function asignaciones(): AsignacionesDocente
    {
        return $this->container->make(AsignacionesDocente::class);
    }
}
