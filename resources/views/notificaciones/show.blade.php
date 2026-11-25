<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('notificaciones.index') }}" class="text-sm font-medium text-indigo-700 hover:text-indigo-900">← Bandeja</a>
            <h2 class="mt-1 text-xl font-semibold leading-tight text-slate-800">{{ $notificacion->asunto }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'notificacion-enviada')
                <p class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">La notificación se envió correctamente.</p>
            @endif

            @php
                $nombreRemitente = $notificacion->remitente?->nombreVisible() ?? 'Remitente';
                $etiquetaRemitente = $notificacion->remitente?->etiquetaConRol() ?? $nombreRemitente;
            @endphp

            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <dl class="flex flex-col gap-2 text-sm text-slate-600">
                    <div class="flex flex-wrap gap-2">
                        <dt class="font-semibold text-slate-800">De:</dt>
                        <dd>{{ $etiquetaRemitente }}</dd>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <dt class="font-semibold text-slate-800">Para:</dt>
                        <dd>
                            {{ $notificacion->destinatarios->map(fn ($destino) => $destino->user?->etiquetaConRol() ?? 'Destinatario')->join(', ') }}
                        </dd>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <dt class="font-semibold text-slate-800">Fecha:</dt>
                        <dd>{{ $notificacion->created_at?->format('d/m/Y H:i') }}</dd>
                    </div>
                </dl>

                <div class="mt-6 whitespace-pre-wrap text-sm leading-6 text-slate-800">{{ $notificacion->cuerpo }}</div>

                @if ($notificacion->archivos->isNotEmpty())
                    <div class="mt-6 border-t border-slate-200 pt-4">
                        <h3 class="text-sm font-semibold text-slate-800">Archivos adjuntos</h3>
                        @foreach ($notificacion->archivos as $archivo)
                            <x-tarea-entrega-archivo :archivo="$archivo" :url="route('notificaciones.archivos.show', [$notificacion, $archivo])" />
                        @endforeach
                    </div>
                @endif
            </article>

            @if ($puedeResponder)
                <div class="mt-6 flex justify-end">
                    <button
                        type="button"
                        class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500"
                        x-on:click="$dispatch('open-modal', 'notificacion-responder')"
                    >
                        Responder
                    </button>
                </div>

                <x-modal name="notificacion-responder" maxWidth="2xl" :show="$errors->hasAny(['cuerpo', 'archivos', 'archivos.*'])" focusable>
                    <form method="POST" action="{{ route('notificaciones.responder', $notificacion) }}" enctype="multipart/form-data" class="p-6">
                        @csrf

                        <h3 class="text-lg font-semibold text-slate-800">Responder</h3>
                        <p class="mt-1 text-sm text-slate-500">Responder a {{ $nombreRemitente }}</p>

                        <div class="mt-4">
                            <x-input-label for="cuerpo" value="Mensaje" />
                            <textarea
                                id="cuerpo"
                                name="cuerpo"
                                rows="6"
                                required
                                maxlength="5000"
                                class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >{{ old('cuerpo') }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('cuerpo')" />
                        </div>

                        <div class="mt-4">
                            <x-input-label for="archivos-respuesta" value="Archivos adjuntos" />
                            <input
                                id="archivos-respuesta"
                                name="archivos[]"
                                type="file"
                                multiple
                                accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.odt,.ods,.txt,application/pdf,image/jpeg,image/png,image/webp"
                                class="mt-1 block w-full text-sm text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                            >
                            <p class="mt-1 text-xs text-slate-500">Opcional. Hasta 5 archivos, de 10 MB cada uno.</p>
                            <x-input-error class="mt-2" :messages="$errors->get('archivos')" />
                            <x-input-error class="mt-2" :messages="$errors->get('archivos.*')" />
                        </div>

                        <div class="mt-6 flex justify-end gap-2">
                            <x-secondary-button type="button" x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">
                                Enviar respuesta
                            </button>
                        </div>
                    </form>
                </x-modal>
            @endif
        </div>
    </div>
</x-app-layout>
