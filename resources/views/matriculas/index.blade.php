<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div><h2 class="text-xl font-semibold leading-tight text-slate-800">Matrículas de mis hijos</h2><p class="mt-1 text-sm text-slate-500">Periodo: {{ $periodo->nombre }} · Completa una ficha por cada hijo.</p></div>
            <a href="{{ route('matriculas.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Nueva ficha</a>
        </div>
    </x-slot>
    <div class="py-8"><div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        @if (session('status') === 'matricula-submitted')<p class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">La ficha se envió correctamente al colegio.</p>@endif
        @if (session('status') === 'matricula-updated')<p class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">Los datos de la ficha se actualizaron correctamente.</p>@endif
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><h3 class="text-lg font-semibold text-slate-900">Fichas enviadas</h3>
            @forelse ($submissions as $submission)
                <div class="mt-4 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-slate-200 p-4"><div><p class="font-medium text-slate-900">{{ $submission->student_name ?: 'Ficha de matrícula #'.$submission->id }}</p><p class="mt-1 text-sm text-slate-500">Enviada el {{ $submission->submitted_at->format('d/m/Y H:i') }}</p></div><div class="flex items-center gap-3"><span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Enviada</span><a href="{{ route('matriculas.edit', $submission) }}" class="rounded-lg border border-indigo-200 px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">Editar ficha</a></div></div>
            @empty
                <p class="mt-4 rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">Aún no has enviado ninguna ficha de matrícula.</p>
            @endforelse
        </section>
    </div></div>
</x-app-layout>
