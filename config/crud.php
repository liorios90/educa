<?php

use App\Models\Sys_Jornada;
use App\Models\Sys_Modalidad;

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
    ],

];
