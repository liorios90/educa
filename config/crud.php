<?php

use App\Models\Sys_Circuito;
use App\Models\Sys_Distrito;
use App\Models\Sys_Jornada;
use App\Models\Sys_Modalidad;
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
                    'name' => 'usuario',
                    'label' => 'Usuario',
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
                    'name' => 'usuario',
                    'label' => 'Usuario',
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
                    'type' => 'integer',
                    'list' => true,
                    'rules' => ['required', 'integer', 'exists:sys_zonas,id'],
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
                    'name' => 'usuario',
                    'label' => 'Usuario',
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
                    'type' => 'integer',
                    'list' => true,
                    'rules' => ['nullable', 'integer', 'exists:sys_distritos,id'],
                ],
            ],
        ],
    ],

];
