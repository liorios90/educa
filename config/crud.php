<?php

use App\Enums\TipoCriterioComportamiento;
use App\Models\Sys_Circuito;
use App\Models\Sys_CriterioComportamiento;
use App\Models\Sys_Distrito;
use App\Models\Sys_Funcion;
use App\Models\Sys_Jornada;
use App\Models\Sys_Modalidad;
use App\Models\Sys_Pais;
use App\Models\Sys_Provincia;
use App\Models\Sys_TipoContrato;
use App\Models\Sys_Zona;

return [

    /*
    |--------------------------------------------------------------------------
    | Catálogos CRUD
    |--------------------------------------------------------------------------
    |
    | Solo estos slugs pueden abrirse en /sistemas/catalogos/{slug}.
    | Añade un bloque por tabla; no pases nombres de tabla desde el request.
    | El model debe ser la clase completa (use App\Models\... arriba).
    |
    */

    'resources' => [
        'jornadas' => [
            'model' => Sys_Jornada::class,
            'title' => 'Jornadas',
            'singular' => 'jornada',
            'order_by' => 'nombre',
            'fields' => [
                [
                    'name' => 'nombre',
                    'label' => 'Nombre',
                    'type' => 'text',
                    'list' => true,
                    'unique' => true,
                    'rules' => ['required', 'string', 'max:50'],
                ],
                [
                    'name' => 'descripcion',
                    'label' => 'Descripción',
                    'type' => 'text',
                    'list' => true,
                    'rules' => ['nullable', 'string', 'max:150'],
                ],
            ],
        ],
        'modalidades' => [
            'model' => Sys_Modalidad::class,
            'title' => 'Modalidades',
            'singular' => 'Modalidad',
            'order_by' => 'nombre',
            'fields' => [
                [
                    'name' => 'nombre',
                    'label' => 'Nombre',
                    'type' => 'text',
                    'list' => true,
                    'unique' => true,
                    'rules' => ['required', 'string', 'max:50'],
                ],
                [
                    'name' => 'descripcion',
                    'label' => 'Descripción',
                    'type' => 'text',
                    'list' => true,
                    'rules' => ['nullable', 'string', 'max:150'],
                ],
            ],
        ],
        'zonas' => [
            'model' => Sys_Zona::class,
            'title' => 'Zonas',
            'singular' => 'zona',
            'order_by' => 'nombre',
            'fields' => [
                [
                    'name' => 'nombre',
                    'label' => 'Nombre',
                    'type' => 'text',
                    'list' => true,
                    'rules' => ['required', 'string', 'max:255'],
                ],
                [
                    'name' => 'descripcion',
                    'label' => 'Descripción',
                    'type' => 'text',
                    'list' => true,
                    'rules' => ['required', 'string', 'max:255'],
                ],

                [
                    'name' => 'activo',
                    'label' => 'Activo',
                    'type' => 'boolean',
                    'list' => true,
                    'rules' => ['required', 'boolean'],
                ],
            ],
        ],
        'distritos' => [
            'model' => Sys_Distrito::class,
            'title' => 'Distritos',
            'singular' => 'distrito',
            'order_by' => 'nombre',
            'fields' => [
                [
                    'name' => 'nombre',
                    'label' => 'Nombre',
                    'type' => 'text',
                    'list' => true,
                    'rules' => ['required', 'string', 'max:255'],
                ],
                [
                    'name' => 'provincia',
                    'label' => 'Provincia',
                    'type' => 'textarea',
                    'list' => true,
                    'rules' => ['required', 'string'],
                ],
                [
                    'name' => 'descripcion',
                    'label' => 'Descripción',
                    'type' => 'text',
                    'list' => true,
                    'rules' => ['required', 'string', 'max:255'],
                ],

                [
                    'name' => 'activo',
                    'label' => 'Activo',
                    'type' => 'boolean',
                    'list' => true,
                    'rules' => ['required', 'boolean'],
                ],
                [
                    'name' => 'zona_id',
                    'label' => 'Zona',
                    'type' => 'select',
                    'list' => true,
                    'rules' => ['required', 'integer', 'exists:sys_zonas,id'],
                    'related_model' => Sys_Zona::class,
                    'option_label' => 'nombre',
                    'relation' => 'zona',
                ],
            ],
        ],
        'paises' => [
            'model' => Sys_Pais::class,
            'title' => 'Países',
            'singular' => 'país',
            'order_by' => 'nombre',
            'fields' => [
                [
                    'name' => 'nombre',
                    'label' => 'Nombre',
                    'type' => 'text',
                    'list' => true,
                    'rules' => ['required', 'string', 'max:255'],
                ],
                [
                    'name' => 'descripcion',
                    'label' => 'Descripción',
                    'type' => 'text',
                    'list' => true,
                    'rules' => ['required', 'string', 'max:255'],
                ],

                [
                    'name' => 'activo',
                    'label' => 'Activo',
                    'type' => 'boolean',
                    'list' => true,
                    'rules' => ['required', 'boolean'],
                ],
            ],
        ],
        'provincias' => [
            'model' => Sys_Provincia::class,
            'title' => 'Provincias',
            'singular' => 'provincia',
            'order_by' => 'nombre',
            'fields' => [
                [
                    'name' => 'nombre',
                    'label' => 'Nombre',
                    'type' => 'text',
                    'list' => true,
                    'rules' => ['required', 'string', 'max:255'],
                ],
                [
                    'name' => 'descripcion',
                    'label' => 'Descripción',
                    'type' => 'text',
                    'list' => true,
                    'rules' => ['required', 'string', 'max:255'],
                ],

                [
                    'name' => 'activo',
                    'label' => 'Activo',
                    'type' => 'boolean',
                    'list' => true,
                    'rules' => ['required', 'boolean'],
                ],
                [
                    'name' => 'pais_id',
                    'label' => 'País',
                    'type' => 'select',
                    'list' => true,
                    'rules' => ['required', 'integer', 'exists:sys_paises,id'],
                    'related_model' => Sys_Pais::class,
                    'option_label' => 'nombre',
                    'relation' => 'pais',
                ],
            ],
        ],
        'circuitos' => [
            'model' => Sys_Circuito::class,
            'title' => 'Circuitos',
            'singular' => 'circuito',
            'order_by' => 'nombre',
            'fields' => [
                [
                    'name' => 'nombre',
                    'label' => 'Nombre',
                    'type' => 'text',
                    'list' => true,
                    'rules' => ['required', 'string', 'max:255'],
                ],
                [
                    'name' => 'descripcion',
                    'label' => 'Descripción',
                    'type' => 'text',
                    'list' => true,
                    'rules' => ['required', 'string', 'max:255'],
                ],

                [
                    'name' => 'activo',
                    'label' => 'Activo',
                    'type' => 'boolean',
                    'list' => true,
                    'rules' => ['required', 'boolean'],
                ],
                [
                    'name' => 'distrito_id',
                    'label' => 'Distrito',
                    'type' => 'select',
                    'list' => true,
                    'rules' => ['nullable', 'integer', 'exists:sys_distritos,id'],
                    'related_model' => Sys_Distrito::class,
                    'option_label' => 'nombre',
                    'relation' => 'distrito',
                ],
            ],
        ],
        'funciones' => [
            'model' => Sys_Funcion::class,
            'title' => 'Funciones',
            'singular' => 'función',
            'order_by' => 'nombre',
            'fields' => [
                [
                    'name' => 'nombre',
                    'label' => 'Nombre',
                    'type' => 'text',
                    'list' => true,
                    'rules' => ['required', 'string', 'max:255'],
                ],
                [
                    'name' => 'descripcion',
                    'label' => 'Descripción',
                    'type' => 'text',
                    'list' => true,
                    'rules' => ['required', 'string', 'max:255'],
                ],
                [
                    'name' => 'activo',
                    'label' => 'Activo',
                    'type' => 'boolean',
                    'list' => true,
                    'rules' => ['required', 'boolean'],
                ],
            ],
        ],
        'tipos-contratos' => [
            'model' => Sys_TipoContrato::class,
            'title' => 'Tipos de contrato',
            'singular' => 'tipo de contrato',
            'order_by' => 'nombre',
            'fields' => [
                [
                    'name' => 'codigo',
                    'label' => 'Código',
                    'type' => 'text',
                    'list' => true,
                    'rules' => ['required', 'string', 'max:45'],
                ],
                [
                    'name' => 'nombre',
                    'label' => 'Nombre',
                    'type' => 'text',
                    'list' => true,
                    'rules' => ['required', 'string', 'max:255'],
                ],
                [
                    'name' => 'descripcion',
                    'label' => 'Descripción',
                    'type' => 'text',
                    'list' => true,
                    'rules' => ['required', 'string', 'max:255'],
                ],
                [
                    'name' => 'activo',
                    'label' => 'Activo',
                    'type' => 'boolean',
                    'list' => true,
                    'rules' => ['required', 'boolean'],
                ],
            ],
        ],
        'criterios-comportamiento' => [
            'model' => Sys_CriterioComportamiento::class,
            'title' => 'Criterios de comportamiento',
            'singular' => 'criterio de comportamiento',
            'new_label' => 'Nuevo criterio',
            'order_by' => 'codigo',
            'messages' => [
                'codigo.required' => 'Escribe el código del criterio.',
                'codigo.unique' => 'Ese código ya existe en el catálogo.',
                'descripcion.required' => 'Escribe la descripción del criterio.',
                'tipo.required' => 'Selecciona el tipo de habilidad.',
                'tipo.in' => 'Selecciona un tipo de habilidad: cognitiva, social o emocional.',
                'activo.required' => 'Indica si el criterio está activo.',
                'obligatorio.required' => 'Indica si el criterio es obligatorio.',
            ],
            'fields' => [
                [
                    'name' => 'codigo',
                    'label' => 'Código',
                    'type' => 'text',
                    'list' => true,
                    'unique' => true,
                    'rules' => ['required', 'string', 'max:20'],
                ],
                [
                    'name' => 'descripcion',
                    'label' => 'Descripción',
                    'type' => 'textarea',
                    'list' => true,
                    'rules' => ['required', 'string', 'max:500'],
                ],
                [
                    'name' => 'tipo',
                    'label' => 'Tipo de habilidad',
                    'type' => 'select',
                    'list' => true,
                    'options' => array_map(
                        fn (TipoCriterioComportamiento $tipo): array => [
                            'id' => $tipo->value,
                            'label' => $tipo->label(),
                        ],
                        TipoCriterioComportamiento::cases(),
                    ),
                    'rules' => ['required', 'string', 'in:cognitiva,social,emocional'],
                ],
                [
                    'name' => 'activo',
                    'label' => 'Activo',
                    'type' => 'boolean',
                    'list' => true,
                    'rules' => ['required', 'boolean'],
                ],
                [
                    'name' => 'obligatorio',
                    'label' => 'Obligatorio',
                    'type' => 'boolean',
                    'list' => true,
                    'rules' => ['required', 'boolean'],
                ],
            ],
        ],
    ],

];
