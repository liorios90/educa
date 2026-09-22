<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">
            {{ $item->label }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <section class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
                <div class="landing-mesh pointer-events-none absolute inset-0 opacity-80"></div>
                <div class="landing-grid pointer-events-none absolute inset-0 opacity-30"></div>
                <div class="relative flex flex-col gap-3 px-6 py-6 sm:px-8 sm:py-8">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-indigo-500">Unidad educativa</p>
                    <h3 class="text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl">{{ $item->label }}</h3>
                    <p class="max-w-3xl text-sm leading-6 text-slate-600 sm:text-base">{{ $intro }}</p>
                </div>
            </section>

            @forelse ($sections as $section)
                <section class="{{ $loop->first ? 'mt-8' : 'mt-10' }}">
                    @if ($section['title'] !== '')
                        <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">{{ $section['title'] }}</h3>
                    @endif

                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($section['items'] as $card)
                            <x-hub-card
                                :href="route($card['item']->route_name)"
                                :label="$card['item']->label"
                                :icon="$card['icon']"
                                :description="$card['description']"
                            />
                        @endforeach
                    </div>
                </section>
            @empty
                <p class="mt-8 rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                    Aún no hay botones en este menú.
                </p>
            @endforelse
        </div>
    </div>
</x-app-layout>
