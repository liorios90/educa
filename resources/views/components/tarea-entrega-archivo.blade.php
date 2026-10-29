@props(['archivo', 'url'])

<div class="mt-3 overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
    @if ($archivo->esImagen())
        <div class="flex items-center justify-center p-2" style="min-height: 16rem;">
            <img
                src="{{ $url }}"
                alt="{{ $archivo->nombre_original }}"
                class="object-contain"
                style="max-height: 28rem; max-width: 100%;"
            >
        </div>
    @elseif ($archivo->esPdf())
        <div style="height: 28rem;">
            <iframe
                src="{{ $url }}#toolbar=1&view=FitH"
                title="{{ $archivo->nombre_original }}"
                style="display: block; width: 100%; height: 100%; border: 0;"
            ></iframe>
        </div>
    @endif
    <p class="border-t border-slate-200 bg-white px-3 py-2 text-sm">
        <a class="font-medium text-indigo-700 hover:text-indigo-900" href="{{ $url }}">Descargar {{ $archivo->nombre_original }}</a>
    </p>
</div>
