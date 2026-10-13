<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('Admin.matriculas.index') }}" class="text-sm font-medium text-indigo-700 hover:text-indigo-900">← Diseño del formulario</a>
            <h2 class="mt-1 text-xl font-semibold leading-tight text-slate-800">Respuestas de matrícula · {{ $periodo->nombre }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto flex max-w-5xl flex-col gap-4 px-4 sm:px-6 lg:px-8">
            @forelse ($submissions as $submission)
                <details class="group rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-3 p-5">
                        <div><h3 class="font-semibold text-slate-900">{{ $submission->student_name ?: 'Estudiante sin nombre' }}</h3><p class="mt-1 text-sm text-slate-500">Enviada por {{ $submission->user->name }} · {{ $submission->submitted_at->format('d/m/Y H:i') }}</p></div>
                        <span class="text-sm font-medium text-indigo-700 group-open:hidden">Ver ficha ↓</span><span class="hidden text-sm font-medium text-indigo-700 group-open:inline">Ocultar ficha ↑</span>
                    </summary>
                    <div class="grid gap-4 border-t border-slate-100 p-5 sm:grid-cols-2">
                        @foreach ($submission->responses ?? [] as $fieldId => $value)
                            @php($label = $submission->schema_snapshot[$fieldId]['label'] ?? null)
                            @if ($label && filled($value))
                                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</dt><dd class="mt-1 whitespace-pre-line text-sm text-slate-900">{{ $value }}</dd></div>
                            @endif
                        @endforeach
                    </div>
                </details>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center text-slate-500">Todavía no se han recibido fichas de matrícula.</div>
            @endforelse
            {{ $submissions->links() }}
        </div>
    </div>
</x-app-layout>
