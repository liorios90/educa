@props(['tarea'])

@if ($tarea->contenido_tipo === \App\Enums\TipoContenidoTarea::Imagen && $tarea->contenido_path)
    <div {{ $attributes->merge(['class' => 'flex items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-slate-50']) }} style="height: 90vh; min-height: 1100px;">
        <img
            src="{{ route('Docente.tareas.archivo', $tarea) }}"
            alt="{{ $tarea->contenido_nombre }}"
            class="object-contain"
            style="max-height: 100%; max-width: 100%;"
        >
    </div>
@elseif ($tarea->contenido_tipo === \App\Enums\TipoContenidoTarea::Pdf && $tarea->contenido_path)
    <div {{ $attributes->merge(['class' => 'overflow-hidden rounded-lg border border-slate-200 bg-slate-50']) }} style="height: 90vh; min-height: 1100px;">
        <iframe
            src="{{ route('Docente.tareas.archivo', $tarea) }}#toolbar=1&view=FitH"
            title="{{ $tarea->contenido_nombre }}"
            style="display: block; width: 100%; height: 100%; border: 0;"
        ></iframe>
    </div>
@endif
