<?php

namespace App\Navigation;

use App\Models\NavigationItem;
use Illuminate\Support\Collection;

final class HubGuide
{
    /**
     * @var array<string, array{section: string, description: string, icon: string}>
     */
    private const ITEMS = [
        'sistemas.crud.jornadas.index' => [
            'section' => 'Jornada y modalidad',
            'description' => 'Turnos en los que funciona la institución: matutina, vespertina o nocturna.',
            'icon' => 'clock',
        ],
        'sistemas.crud.modalidades.index' => [
            'section' => 'Jornada y modalidad',
            'description' => 'Cómo se imparte la educación: presencial, semipresencial u otra modalidad.',
            'icon' => 'academic-cap',
        ],
        'sistemas.crud.paises.index' => [
            'section' => 'Ubicación de la institución',
            'description' => 'Países usados en las fichas de estudiantes, representantes y personal.',
            'icon' => 'globe-alt',
        ],
        'sistemas.crud.provincias.index' => [
            'section' => 'Ubicación de la institución',
            'description' => 'Provincia donde funciona la institución u otras sedes.',
            'icon' => 'flag',
        ],
        'sistemas.crud.zonas.index' => [
            'section' => 'Ubicación de la institución',
            'description' => 'Zona educativa del Ministerio a la que pertenece la institución.',
            'icon' => 'map',
        ],
        'sistemas.crud.distritos.index' => [
            'section' => 'Ubicación de la institución',
            'description' => 'Distrito educativo para la gestión y los reportes institucionales.',
            'icon' => 'building-office-2',
        ],
        'sistemas.crud.circuitos.index' => [
            'section' => 'Ubicación de la institución',
            'description' => 'Circuito educativo más cercano a la institución.',
            'icon' => 'map-pin',
        ],
        'sistemas.crud.funciones.index' => [
            'section' => 'Personal de la institución',
            'description' => 'Cargos del personal: docente, secretaría, inspector, rectorado y más.',
            'icon' => 'identification',
        ],
        'sistemas.crud.tipos-contratos.index' => [
            'section' => 'Personal de la institución',
            'description' => 'Tipo de relación laboral: nombramiento, contrato ocasional u otro.',
            'icon' => 'briefcase',
        ],
        'sistemas.crud.criterios-comportamiento.index' => [
            'section' => 'Evaluación',
            'description' => 'Habilidades socioemocionales con las que se describe el comportamiento, según el Acuerdo Ministerial 082-A.',
            'icon' => 'academic-cap',
        ],
        'sistemas.estructura' => [
            'section' => 'Oferta educativa',
            'description' => 'Niveles, subniveles y grados con los que se organiza la institución.',
            'icon' => 'building-library',
        ],
        'sistemas.curriculo' => [
            'section' => 'Oferta educativa',
            'description' => 'Áreas curriculares y asignaturas que se dictan en la institución.',
            'icon' => 'book-open',
        ],
        'Docente.calificar.index' => [
            'section' => 'Registro de clase',
            'description' => 'Califica un insumo nuevo del parcial: tarea, lección, actividad o proyecto.',
            'icon' => 'book-open',
        ],
        'Docente.calificar.editar' => [
            'section' => 'Registro de clase',
            'description' => 'Corrige las calificaciones de un insumo ya registrado.',
            'icon' => 'folder',
        ],
        'Docente.asistencia.index' => [
            'section' => 'Registro de clase',
            'description' => 'Registra la asistencia una vez al día o en cada asignatura.',
            'icon' => 'calendar-days',
        ],
        'Docente.tareas.index' => [
            'section' => 'Registro de clase',
            'description' => 'Envía tareas al aula o a alumnos puntuales, con fecha y material adjunto.',
            'icon' => 'clipboard-document-list',
        ],
        'Docente.comportamiento.index' => [
            'section' => 'Registro de clase',
            'description' => 'Califica cada indicador del periodo con la escala de comportamiento. No modifica el promedio de las asignaturas.',
            'icon' => 'shield-check',
        ],
    ];

    private const SECTION_ORDER = [
        'Jornada y modalidad',
        'Ubicación de la institución',
        'Personal de la institución',
        'Oferta educativa',
        'Evaluación',
        'Registro de clase',
        'Otras opciones',
    ];

    public static function intro(string $label): string
    {
        return match ($label) {
            'Catálogos' => 'Listados oficiales que usa secretaría, rectorado y sistemas para matrícula, personal y reportes. Elija un catálogo para consultarlo o actualizarlo.',
            'Estructura' => 'Organice la oferta educativa de la institución: niveles, grados, áreas y asignaturas.',
            'Insumos' => 'Califica los insumos del parcial, envía tareas, registra la asistencia y el comportamiento.',
            default => 'Seleccione una opción para continuar.',
        };
    }

    /**
     * @param  Collection<int, NavigationItem>  $buttons
     * @return list<array{title: string, items: list<array{item: NavigationItem, description: ?string, icon: string}>}>
     */
    public static function sections(Collection $buttons): array
    {
        $grouped = [];

        foreach ($buttons as $button) {
            $routeName = is_string($button->route_name) ? $button->route_name : '';
            $guide = self::ITEMS[$routeName] ?? null;
            $title = $guide['section'] ?? 'Otras opciones';

            $grouped[$title][] = [
                'item' => $button,
                'description' => $guide['description'] ?? null,
                'icon' => $guide['icon'] ?? ($button->icon ?: 'squares-2x2'),
            ];
        }

        $sections = [];

        foreach (self::SECTION_ORDER as $title) {
            if (! isset($grouped[$title])) {
                continue;
            }

            $sections[] = [
                'title' => $title,
                'items' => $grouped[$title],
            ];
        }

        if (count($sections) === 1) {
            $sections[0]['title'] = '';
        }

        return $sections;
    }
}
