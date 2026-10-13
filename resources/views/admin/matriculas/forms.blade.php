<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Formularios de matrícula</h2>
            <p class="mt-1 text-sm text-slate-500">Administra los formularios de cada periodo. Solo uno puede estar activo por periodo.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto flex max-w-6xl flex-col gap-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <p class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                    @switch(session('status'))
                        @case('matricula-form-has-submissions') Este formulario ya tiene fichas enviadas y no se puede eliminar. @break
                        @case('matricula-form-activated') El formulario quedó activo para su periodo. @break
                        @case('matricula-form-deleted') El formulario se eliminó. @break
                        @default Los cambios se guardaron correctamente.
                    @endswitch
                </p>
            @endif

            @if ($periodos->isNotEmpty())
                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">Crear formulario</h3>
                    <p class="mt-1 text-sm text-slate-500">El nuevo formulario copia la estructura de otro formulario del mismo periodo o, si no existe, del periodo anterior.</p>
                    <form method="POST" action="{{ route('Admin.matriculas.forms.store') }}" class="mt-4 flex flex-wrap items-end gap-3">
                        @csrf
                        <label class="grid min-w-56 gap-1 text-sm font-medium text-slate-700">Periodo escolar
                            <select name="periodo_id" required class="rounded-lg border-slate-300 text-sm">
                                @foreach ($periodos as $periodo)
                                    <option value="{{ $periodo->id }}">{{ $periodo->nombre }} ({{ $periodo->fecha_inicio->format('Y-m-d') }} – {{ $periodo->fecha_fin->format('Y-m-d') }})</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="grid min-w-56 gap-1 text-sm font-medium text-slate-700">Nombre del formulario
                            <input name="title" required maxlength="120" placeholder="Ej. Matrícula general" class="rounded-lg border-slate-300 text-sm">
                        </label>
                        <button class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Crear formulario</button>
                    </form>
                </section>
            @endif

            @forelse ($periodos as $periodo)
                @php($periodKey = $periodo->fecha_inicio->toDateString().'|'.$periodo->fecha_fin->toDateString())
                @php($forms = $formsByPeriod->get($periodKey, collect()))
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                        <h3 class="font-semibold text-slate-900">{{ $periodo->nombre }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $periodo->fecha_inicio->format('d/m/Y') }} – {{ $periodo->fecha_fin->format('d/m/Y') }}</p>
                    </div>
                    @if ($forms->isEmpty())
                        <p class="p-5 text-sm text-slate-500">Todavía no hay formularios para este periodo.</p>
                    @else
                        <div class="divide-y divide-slate-100">
                            @foreach ($forms as $form)
                                <div class="flex flex-wrap items-center justify-between gap-4 p-5">
                                    <div>
                                        <h4 class="font-semibold text-slate-900">{{ $form->title }}</h4>
                                        <p class="mt-1 text-sm text-slate-500">{{ $form->submissions_count }} fichas recibidas</p>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        @if ($form->is_active)
                                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Activo para las familias</span>
                                        @else
                                            <form method="POST" action="{{ route('Admin.matriculas.forms.activate', $form) }}">
                                                @csrf @method('PATCH')
                                                <button class="rounded-lg border border-emerald-200 px-3 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50">Activar</button>
                                            </form>
                                        @endif
                                        <a href="{{ route('Admin.matriculas.forms.edit', $form) }}" class="rounded-lg border border-indigo-200 px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">Editar formulario</a>
                                        <a href="{{ route('Admin.matriculas.submissions', $form) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Ver fichas</a>
                                        <form method="POST" action="{{ route('Admin.matriculas.forms.destroy', $form) }}" onsubmit="return confirm('¿Eliminar este formulario?');">
                                            @csrf @method('DELETE')
                                            <button @disabled($form->submissions_count > 0) class="rounded-lg border border-rose-200 px-3 py-2 text-sm font-semibold text-rose-700 enabled:hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-40" title="No se puede eliminar un formulario que ya tiene fichas">Eliminar</button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            @empty
                <p class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-500">Primero debes crear un periodo escolar para diseñar formularios de matrícula.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
