@if ($isSistemas)
    <section class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
        <div class="landing-mesh pointer-events-none absolute inset-0 opacity-90"></div>
        <div class="landing-grid pointer-events-none absolute inset-0 opacity-40"></div>

        <div class="relative mx-auto flex w-full flex-col items-center justify-center gap-6 px-8 py-12 text-center md:px-12 md:py-16">
            <div class="mx-auto flex h-32 w-32 shrink-0 items-center justify-center overflow-hidden rounded-3xl bg-white shadow-xl shadow-slate-900/5 ring-1 ring-slate-200/80">
                @if ($logoUrl)
                    <img
                        src="{{ $logoUrl }}"
                        alt="Logo de Sistemas"
                        class="h-full w-full object-contain p-3"
                    >
                @else
                    <x-application-logo class="h-16 w-16 fill-current text-indigo-500" />
                @endif
            </div>

            <div class="w-full text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-indigo-500">Sistemas</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl">
                    {{ config('app.name', 'Educa') }}
                </h1>
            </div>
        </div>
    </section>
@elseif ($establecimiento)
    <section class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
        <div class="landing-mesh pointer-events-none absolute inset-0 opacity-90"></div>
        <div class="landing-grid pointer-events-none absolute inset-0 opacity-40"></div>

        <div class="relative flex flex-col items-center gap-8 px-8 py-12 text-center md:flex-row md:px-12 md:py-16 md:text-left">
            <div class="flex h-32 w-32 shrink-0 items-center justify-center overflow-hidden rounded-3xl bg-white shadow-xl shadow-slate-900/5 ring-1 ring-slate-200/80">
                @if ($logoUrl)
                    <img
                        src="{{ $logoUrl }}"
                        alt="Logo de {{ $establecimiento->nombre }}"
                        class="h-full w-full object-contain p-3"
                    >
                @else
                    <span class="text-3xl font-semibold tracking-tight text-indigo-600">{{ $establecimiento->monograma() }}</span>
                @endif
            </div>

            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-indigo-500">Establecimiento</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl">
                    {{ $establecimiento->nombre }}
                </h1>
                <p class="mt-3 text-sm text-slate-500">
                    Código AMIE {{ $establecimiento->codigo_amie }}
                    @if (filled($establecimiento->regimen))
                        <span class="px-2 text-slate-300">·</span>
                        Régimen {{ $establecimiento->regimen }}
                    @endif
                </p>
            </div>
        </div>
    </section>
@else
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="p-6 text-slate-700">
            Bienvenido al panel. Usa el menú para acceder a las secciones de tu rol.
        </div>
    </div>
@endif
