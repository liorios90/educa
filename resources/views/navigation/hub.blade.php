<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">
            {{ $item->label }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($buttons as $button)
                    <a
                        href="{{ route($button->route_name) }}"
                        class="flex min-h-28 items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-6 text-center text-base font-semibold text-slate-800 shadow-sm transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-800"
                    >
                        {{ $button->label }}
                    </a>
                @empty
                    <p class="col-span-full rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                        Aún no hay botones en este menú.
                    </p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
