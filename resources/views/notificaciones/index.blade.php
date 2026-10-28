<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-slate-800">Notificaciones</h2>
                <p class="mt-1 text-sm text-slate-500">Bandeja de entrada y mensajes enviados.</p>
            </div>
            <a href="{{ route('notificaciones.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">
                Redactar
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <nav class="mb-6 flex flex-wrap gap-2" aria-label="Bandejas">
                <a
                    href="{{ route('notificaciones.index') }}"
                    class="rounded-lg px-4 py-2 text-sm font-semibold {{ $enviadas ? 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' : 'bg-indigo-600 text-white' }}"
                >
                    Recibidas
                </a>
                <a
                    href="{{ route('notificaciones.index', ['bandeja' => 'enviadas']) }}"
                    class="rounded-lg px-4 py-2 text-sm font-semibold {{ $enviadas ? 'bg-indigo-600 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}"
                >
                    Enviadas
                </a>
            </nav>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                @forelse ($notificaciones as $notificacion)
                    @php
                        $nombreRemitente = $notificacion->remitente?->etiquetaConRol() ?? 'Remitente';
                        $noLeida = (bool) ($notificacion->no_leida ?? false);
                    @endphp
                    <a
                        href="{{ route('notificaciones.show', $notificacion) }}"
                        class="flex flex-col gap-1 border-b border-slate-100 px-5 py-4 last:border-b-0 hover:bg-slate-50 {{ $noLeida ? 'bg-indigo-50/60' : '' }}"
                    >
                        <div class="flex flex-wrap items-baseline justify-between gap-3">
                            <p class="text-sm {{ $noLeida ? 'font-semibold text-slate-900' : 'font-medium text-slate-700' }}">
                                {{ $enviadas ? 'Para: '.$notificacion->destinatarios->map(fn ($destino) => $destino->user?->etiquetaConRol() ?? 'Destinatario')->join(', ') : 'De: '.$nombreRemitente }}
                            </p>
                            <p class="text-xs text-slate-400">{{ $notificacion->created_at?->format('d/m/Y H:i') }}</p>
                        </div>
                        <p class="text-sm {{ $noLeida ? 'font-semibold text-slate-900' : 'text-slate-800' }}">{{ $notificacion->asunto }}</p>
                    </a>
                @empty
                    <p class="px-6 py-8 text-center text-sm text-slate-500">
                        {{ $enviadas ? 'Aún no has enviado notificaciones.' : 'No tienes notificaciones recibidas.' }}
                    </p>
                @endforelse
            </section>

            @if ($notificaciones->hasPages())
                <div class="mt-6">
                    {{ $notificaciones->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
