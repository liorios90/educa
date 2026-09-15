<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">
            Reportes
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($reports as $report)
                    <a href="{{ route('reports.show', $report) }}" class="rounded-2xl border border-slate-200 bg-white px-5 py-6 shadow-sm transition hover:border-indigo-300 hover:bg-indigo-50">
                        <p class="text-base font-semibold text-slate-800">{{ $report->name }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $report->source }}</p>
                    </a>
                @empty
                    <p class="col-span-full rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                        No hay reportes disponibles para tu usuario.
                    </p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
