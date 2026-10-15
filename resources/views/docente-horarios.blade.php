<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Horarios</h2>
            <p class="mt-1 text-sm text-slate-500">Tus clases organizadas por día, hora y aula.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if ($horariosDocente->isNotEmpty())
                @php
                    $diasSemana = [
                        1 => 'Lunes',
                        2 => 'Martes',
                        3 => 'Miércoles',
                        4 => 'Jueves',
                        5 => 'Viernes',
                        6 => 'Sábado',
                        //7 => 'Domingo',
                    ];
                    $minutoInicioHorario = 7 * 60;
                    $minutoFinHorario = 18 * 60;

                    foreach ($horariosDocente as $clase) {
                        [$horaInicio, $minutoInicio] = array_map('intval', explode(':', $clase['inicio']));
                        [$horaFin, $minutoFin] = array_map('intval', explode(':', $clase['fin']));
                        $minutoInicioHorario = min($minutoInicioHorario, intdiv($horaInicio * 60 + $minutoInicio, 60) * 60);
                        $minutoFinHorario = max($minutoFinHorario, (int) (ceil(($horaFin * 60 + $minutoFin) / 60) * 60));
                    }

                    $minutoFinHorario = min($minutoFinHorario, 24 * 60);
                    $franjasHorario = [];

                    for ($minuto = $minutoInicioHorario; $minuto < $minutoFinHorario; $minuto += 15) {
                        $franjasHorario[] = [
                            'minuto' => $minuto,
                            'hora' => sprintf('%02d:%02d', intdiv($minuto, 60), $minuto % 60),
                        ];
                    }
                @endphp

                <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-lg shadow-slate-200/60">
                    <div class="flex flex-col gap-3 border-b border-slate-200 bg-gradient-to-r from-indigo-50 via-white to-cyan-50 px-5 py-5 sm:px-7">
                        <span class="w-fit rounded-full border border-indigo-100 bg-white/80 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-indigo-700">Panel docente</span>
                        <div>
                            <h3 class="text-xl font-bold tracking-tight text-slate-900">Mi horario semanal</h3>
                            <p class="mt-1 text-sm text-slate-600">Consulta tus clases por día, hora, asignatura y aula.</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto p-3 sm:p-5">
                        <div class="min-w-[1120px] rounded-2xl border border-slate-200 bg-slate-50/70 p-2">
                            <div
                                class="grid overflow-hidden rounded-xl bg-white"
                                style="grid-template-columns: 4.5rem repeat(7, minmax(9rem, 1fr)); grid-template-rows: 3.25rem repeat({{ count($franjasHorario) }}, 1.5rem);"
                            >
                                <div class="z-20 flex items-center justify-center border-b border-r border-slate-200 bg-slate-100 text-xs font-bold uppercase tracking-wide text-slate-500" style="grid-column: 1; grid-row: 1">Hora</div>
                                @foreach ($diasSemana as $numeroDia => $nombreDia)
                                    <div class="z-20 flex items-center justify-center border-b border-r border-slate-200 bg-slate-100 px-2 text-sm font-bold text-slate-700" style="grid-column: {{ $numeroDia + 1 }}; grid-row: 1">{{ $nombreDia }}</div>
                                @endforeach

                                @foreach ($franjasHorario as $indiceFranja => $franja)
                                    <div class="z-10 flex justify-center border-b border-r border-slate-200 bg-slate-50 pt-0.5 text-[10px] font-semibold text-slate-500" style="grid-column: 1; grid-row: {{ $indiceFranja + 2 }}">
                                        @if ($franja['minuto'] % 60 === 0)
                                            {{ $franja['hora'] }}
                                        @endif
                                    </div>
                                    @foreach ($diasSemana as $numeroDia => $nombreDia)
                                        <div class="border-b border-r border-slate-100 bg-white" style="grid-column: {{ $numeroDia + 1 }}; grid-row: {{ $indiceFranja + 2 }}"></div>
                                    @endforeach
                                @endforeach

                                @foreach ($horariosDocente as $clase)
                                    @php
                                        [$horaClaseInicio, $minutoClaseInicio] = array_map('intval', explode(':', $clase['inicio']));
                                        [$horaClaseFin, $minutoClaseFin] = array_map('intval', explode(':', $clase['fin']));
                                        $minutosClaseInicio = $horaClaseInicio * 60 + $minutoClaseInicio;
                                        $minutosClaseFin = $horaClaseFin * 60 + $minutoClaseFin;
                                        $filaClaseInicio = intdiv($minutosClaseInicio - $minutoInicioHorario, 15) + 2;
                                        $filasClase = max(1, (int) ceil(($minutosClaseFin - $minutosClaseInicio) / 15));
                                    @endphp
                                    <article
                                        class="z-10 m-1 flex min-h-0 flex-col justify-center overflow-hidden rounded-xl border border-indigo-200 bg-gradient-to-br from-indigo-50 to-white px-2.5 py-1.5 text-indigo-950 shadow-sm ring-1 ring-indigo-100/70 transition hover:z-30 hover:shadow-md"
                                        style="grid-column: {{ $clase['dia'] + 1 }}; grid-row: {{ $filaClaseInicio }} / span {{ $filasClase }}"
                                        title="{{ ($diasSemana[$clase['dia']] ?? 'Día').' · '.$clase['inicio'].'–'.$clase['fin'].' · '.$clase['asignatura'].' ·  '.$clase['aula'] }}"
                                    >
                                        <span class="truncate text-xs font-bold leading-tight">{{ $clase['asignatura'] }}</span>
                                        <span class="mt-0.5 truncate text-[10px] font-medium text-indigo-800">{{ $clase['inicio'] }}–{{ $clase['fin'] }} · {{ $clase['docente'] ?: 'Docente' }}</span>
                                        <span class="mt-0.5 truncate text-[10px] text-slate-600">Aula: {{ $clase['aula'] }}</span>
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>
            @elseif ($asignaturasYAulasDocente->isNotEmpty())
                <section>
                    <div class="mb-5">
                        <h3 class="text-lg font-semibold text-slate-900">Mis asignaturas y aulas</h3>
                        <p class="mt-1 text-sm text-slate-500">Aún no tienes clases programadas. Estas son las asignaturas y aulas que tienes asignadas.</p>
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($asignaturasYAulasDocente as $asignacion)
                            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Asignatura</p>
                                <h4 class="mt-1 text-base font-semibold text-slate-900">{{ $asignacion['asignatura'] }}</h4>
                                <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-slate-500">Aula</p>
                                <p class="mt-1 text-sm font-medium text-slate-700">{{ $asignacion['aula'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </section>
            @else
                <div class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-600 shadow-sm">
                    Aún no tienes asignaturas ni aulas asignadas.
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
