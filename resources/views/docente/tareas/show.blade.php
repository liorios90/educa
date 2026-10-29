<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <a href="{{ route('Docente.tareas.index') }}" class="text-sm font-medium text-indigo-700 hover:text-indigo-900">← Tareas</a>
                <h2 class="mt-1 text-xl font-semibold leading-tight text-slate-800">{{ $tarea->nombre }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $tarea->aula?->etiqueta() }} · {{ $tarea->asignatura?->nombre() }} · {{ $tarea->ciclo?->nombre }} · Parcial {{ $tarea->parcial_numero }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'tarea-actualizada')
                <p class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">La tarea se actualizó.</p>
            @endif

            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <dl class="grid gap-3 text-sm text-slate-600 sm:grid-cols-2">
                    <div><dt class="font-semibold text-slate-800">Inicio</dt><dd>{{ $tarea->fecha_inicio->format('d/m/Y') }}</dd></div>
                    <div><dt class="font-semibold text-slate-800">Entrega</dt><dd>{{ $tarea->fecha_fin->format('d/m/Y') }}</dd></div>
                    <div class="sm:col-span-2"><dt class="font-semibold text-slate-800">Contenido</dt><dd>{{ $tarea->contenido_tipo->label() }}</dd></div>
                </dl>
                <div class="prose mt-4 max-w-none text-sm text-slate-800">{!! $tarea->descripcion !!}</div>
                @if ($tarea->contenido_tipo === \App\Enums\TipoContenidoTarea::Texto && $tarea->contenido_texto)
                    <p class="mt-4 whitespace-pre-wrap rounded-lg bg-slate-50 p-4 text-sm text-slate-700">{{ $tarea->contenido_texto }}</p>
                @elseif ($tarea->contenido_tipo === \App\Enums\TipoContenidoTarea::Url && $tarea->contenido_url)
                    <p class="mt-4 text-sm"><a class="font-medium text-indigo-700 hover:text-indigo-900" href="{{ $tarea->contenido_url }}" target="_blank" rel="noopener noreferrer">{{ $tarea->contenido_url }}</a></p>
                @elseif ($tarea->contenido_path)
                    <x-tarea-archivo-preview :tarea="$tarea" class="mt-4" />
                @endif
            </article>
        </div>
    </div>
</x-app-layout>
