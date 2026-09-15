<?php

use App\Models\Sys_Jornada;
use App\Models\Sys_Modalidad;
use App\Models\Sys_Nivel;
use App\Models\User;

return [

    /*
    |--------------------------------------------------------------------------
    | Fuentes de reportes
    |--------------------------------------------------------------------------
    |
    | El controlador lee esta lista y envía los campos al diseñador.
    | key puede ser una columna local (nombre) o una relación (roles.name).
    |
    */

    'sources' => [
        'jornadas' => [
            'label' => 'Jornadas',
            'model' => Sys_Jornada::class,
            'order_by' => 'nombre',
            'fields' => [
                ['key' => 'nombre', 'label' => 'Nombre', 'group' => 'Jornadas'],
                ['key' => 'descripcion', 'label' => 'Descripción', 'group' => 'Jornadas'],
            ],
        ],
        'modalidades' => [
            'label' => 'Modalidades',
            'model' => Sys_Modalidad::class,
            'order_by' => 'nombre',
            'fields' => [
                ['key' => 'nombre', 'label' => 'Nombre', 'group' => 'Modalidades'],
                ['key' => 'descripcion', 'label' => 'Descripción', 'group' => 'Modalidades'],
            ],
        ],
        'niveles' => [
            'label' => 'Niveles',
            'model' => Sys_Nivel::class,
            'order_by' => 'nombre',
            'fields' => [
                ['key' => 'nombre', 'label' => 'Nombre', 'group' => 'Niveles'],
                ['key' => 'descripcion', 'label' => 'Descripción', 'group' => 'Niveles'],
            ],
        ],
        'usuarios' => [
            'label' => 'Usuarios',
            'model' => User::class,
            'order_by' => 'name',
            'fields' => [
                ['key' => 'name', 'label' => 'Nombre', 'group' => 'Usuarios'],
                ['key' => 'email', 'label' => 'Correo', 'group' => 'Usuarios'],
                ['key' => 'roles.name', 'label' => 'Roles', 'group' => 'Roles', 'relation' => 'roles', 'attribute' => 'name'],
            ],
        ],
    ],

];
