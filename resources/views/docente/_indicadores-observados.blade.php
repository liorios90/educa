@php
    $seleccionados = collect($seleccionados)->map(fn (mixed $id): int => (int) $id)->all();
    $totalIndicadores = count($destreza['indicadores']);
    $marcados = count(array_intersect($seleccionados, array_column($destreza['indicadores'], 'id')));
@endphp

<div
    class="mb-2 flex flex-col gap-1"
    x-data="{ marcados: {{ $marcados }}, total: {{ $totalIndicadores }} }"
>
    @foreach ($destreza['indicadores'] as $indicador)
        <label class="flex items-start gap-2 text-xs leading-5 text-slate-600">
            <input
                type="checkbox"
                name="destrezas[{{ $alumnoId }}][{{ $destreza['id'] }}][indicadores][]"
                value="{{ $indicador['id'] }}"
                class="mt-0.5 rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                @checked(in_array($indicador['id'], $seleccionados, true))
                @change="marcados += $event.target.checked ? 1 : -1"
            >
            <span><span class="font-medium text-slate-700">{{ $indicador['codigo'] }}.</span> {{ $indicador['descripcion'] }}</span>
        </label>
    @endforeach
    <p class="text-xs font-medium text-slate-700" x-text="marcados + ' de ' + total + (total === 1 ? ' indicador' : ' indicadores')">
        {{ $marcados }} de {{ $totalIndicadores }} {{ $totalIndicadores === 1 ? 'indicador' : 'indicadores' }}
    </p>
</div>
