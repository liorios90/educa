<article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <button
            type="button"
            class="flex min-w-0 flex-1 items-start gap-3 text-left"
            @click="toggleNivel({{ $nivel->id }})"
        >
            <span
                class="mt-1 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-600 transition"
                :class="{ 'rotate-180 bg-indigo-100 text-indigo-700': openNivelId === {{ $nivel->id }} }"
            >
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
            </span>
            <span class="min-w-0">
                <span class="block font-semibold text-slate-800">
                    {{ $nivel->nombre }}
                    @if ($nivel->siglas)
                        <span class="ml-1 font-normal text-slate-500">({{ $nivel->siglas }})</span>
                    @endif
                </span>
                <span class="mt-1 block text-sm text-slate-500">{{ $nivel->descripcion ?: 'Sin descripción' }}</span>
                <span class="mt-1 block text-xs text-slate-400">{{ $nivel->subniveles->count() }} subniveles</span>
            </span>
        </button>
    </div>

    <div class="space-y-4 border-t border-slate-100 bg-slate-50 px-4 py-4 sm:px-6" x-cloak x-show="openNivelId === {{ $nivel->id }}">
        <div class="flex flex-col gap-3">
            <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Subniveles</h4>

            @forelse ($nivel->subniveles as $subnivel)
                @include('sistemas.curriculo._subnivel', ['nivel' => $nivel, 'subnivel' => $subnivel])
            @empty
                <p class="rounded-xl border border-dashed border-slate-200 bg-white px-4 py-3 text-sm text-slate-500">
                    Este nivel aún no tiene subniveles. Agrégalos en
                    <a href="{{ route('sistemas.estructura') }}" class="font-medium text-indigo-600 hover:text-indigo-500">Nivel - Subnivel - Grado</a>.
                </p>
            @endforelse
        </div>
    </div>
</article>
