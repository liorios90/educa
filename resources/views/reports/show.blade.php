<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-slate-800">
                {{ $report->name }}
            </h2>
            <div class="flex items-center gap-4">
                <a
                    href="{{ route('reports.pdf', $report) }}"
                    class="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700"
                >
                    Descargar PDF
                </a>
                <a href="{{ route('reports.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Volver</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if ($report->usesTableLayout())
                <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    @include('reports._table')
                </div>
            @else
                <div class="flex flex-col gap-6">
                    @forelse ($rows as $row)
                        <div class="relative min-h-[32rem] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                            @foreach ($report->fields as $field)
                                <p
                                    class="absolute max-w-[40%] text-xs font-semibold text-slate-500"
                                    style="left: {{ (int) $field->label_x }}%; top: {{ (int) $field->label_y }}%;"
                                >
                                    {{ $field->label }}
                                </p>
                                <p
                                    class="absolute max-w-[50%] text-sm text-slate-800"
                                    style="left: {{ (int) $field->value_x }}%; top: {{ (int) $field->value_y }}%;"
                                >
                                    {{ $row[$field->column] ?? '—' }}
                                </p>
                            @endforeach
                        </div>
                    @empty
                        <div class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500 shadow-sm">
                            No hay datos para este reporte.
                        </div>
                    @endforelse
                </div>
            @endif

            <div class="mt-4">
                {{ $rows->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
