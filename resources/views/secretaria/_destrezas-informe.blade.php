@if (($informe['por_destrezas'] ?? false) === true)
    <div class="mt-4 rounded-lg border border-slate-200 p-3 text-xs text-slate-600">
        <p class="font-semibold text-slate-700">Escala de destrezas</p>
        <ul class="mt-2 grid grid-cols-1 gap-1 sm:grid-cols-2">
            @foreach ($informe['escala_destrezas'] as $banda)
                <li><span class="font-semibold text-slate-700">{{ $banda['codigo'] }}.</span> {{ $banda['etiqueta'] }}</li>
            @endforeach
        </ul>
        @if (($informe['equivale_nota_nacional'] ?? false) === true)
            <p class="mt-2">En Básica Elemental la calificación numérica es la equivalencia sobre 10 de esas destrezas. La promoción exige al menos 7,00.</p>
        @elseif (($informe['usa_ambitos'] ?? false) === true)
            <p class="mt-2">En Educación Inicial y Preparatoria cada destreza tiene su letra, de A+ a E- o NE. No se promedia.</p>
        @else
            <p class="mt-2">No se promedia una nota sobre 10.</p>
        @endif
    </div>

    @if (($estudiante['destrezas'] ?? []) !== [])
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col" class="px-3 py-2 text-left font-semibold text-slate-700">{{ ($informe['usa_ambitos'] ?? false) ? 'Ámbito' : 'Asignatura' }}</th>
                        <th scope="col" class="px-3 py-2 text-left font-semibold text-slate-700">Destreza</th>
                        @foreach ($estudiante['destrezas'][0]['periodos'] as $periodo)
                            <th scope="col" class="px-3 py-2 text-center font-semibold text-slate-700">{{ $periodo['nombre'] }}</th>
                        @endforeach
                        <th scope="col" class="px-3 py-2 text-center font-semibold text-slate-700">{{ ($informe['usa_ambitos'] ?? false) ? 'Último registro' : 'Resultado' }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($estudiante['destrezas'] as $destreza)
                        <tr>
                            <td class="px-3 py-2 text-slate-800">{{ $destreza['asignatura'] }}</td>
                            <td class="px-3 py-2 text-slate-700">
                                <span class="font-medium">{{ $destreza['codigo'] }}.</span>
                                {{ $destreza['descripcion'] }}
                            </td>
                            @foreach ($destreza['periodos'] as $periodo)
                                <td class="px-3 py-2 text-center font-medium text-slate-800">{{ $periodo['nota'] }}</td>
                            @endforeach
                            <td class="px-3 py-2 text-center font-medium text-slate-800">{{ $destreza['nota'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endif
