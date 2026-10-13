<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('matriculas.index') }}" class="text-sm font-medium text-indigo-700 hover:text-indigo-900">← Mis fichas</a>
            <h2 class="mt-1 text-xl font-semibold leading-tight text-slate-800">{{ $submission ? 'Editar ficha de '.$submission->student_name : $form->title }}</h2>
            <p class="mt-1 text-sm text-slate-500">Periodo: {{ $periodo->nombre }} · Los campos marcados con * son obligatorios.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">Revisa los campos marcados: {{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ $submission ? route('matriculas.update', $submission) : route('matriculas.store') }}" novalidate class="flex flex-col gap-6">
                @csrf
                @if ($submission) @method('PUT') @endif
                <div x-data="{ activeSection: {{ (int) session('activeEnrollmentSectionId', $form->sections->first()?->id ?? 0) }} }" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="overflow-x-auto border-b border-slate-200 bg-slate-50 px-4 pt-3">
                        <div role="tablist" aria-label="Secciones de la ficha" class="flex min-w-max gap-2">
                            @foreach ($form->sections as $section)
                                <button
                                    id="parent-section-tab-{{ $section->id }}"
                                    type="button"
                                    role="tab"
                                    aria-controls="parent-section-panel-{{ $section->id }}"
                                    :aria-selected="activeSection === {{ $section->id }} ? 'true' : 'false'"
                                    :tabindex="activeSection === {{ $section->id }} ? 0 : -1"
                                    @click="activeSection = {{ $section->id }}"
                                    :class="activeSection === {{ $section->id }} ? 'border-indigo-600 bg-white text-indigo-700' : 'border-transparent text-slate-600 hover:bg-white hover:text-slate-900'"
                                    class="rounded-t-lg border-b-2 px-4 py-3 text-sm font-semibold transition"
                                >{{ $section->title }}</button>
                            @endforeach
                        </div>
                    </div>

                    @foreach ($form->sections as $section)
                        <section
                            id="parent-section-panel-{{ $section->id }}"
                            role="tabpanel"
                            aria-labelledby="parent-section-tab-{{ $section->id }}"
                            x-show="activeSection === {{ $section->id }}"
                            x-cloak
                            x-data="{ activeSubsection: {{ (int) ((int) session('activeEnrollmentSectionId', 0) === $section->id ? session('activeEnrollmentSubsectionId', $section->subsections->first()?->id ?? 0) : ($section->subsections->first()?->id ?? 0)) }} }"
                            class="p-5 sm:p-6"
                        >
                            <h3 class="text-lg font-semibold text-slate-900">{{ $section->title }}</h3>

                            @if ($section->fields->isNotEmpty())
                                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                                    @foreach ($section->fields as $field)
                                        @include('matriculas._field', ['field' => $field])
                                    @endforeach
                                </div>
                            @endif

                            @if ($section->subsections->isNotEmpty())
                                <div class="mt-6 border-b border-slate-200">
                                    <div role="tablist" aria-label="Subsecciones de {{ $section->title }}" class="flex gap-2 overflow-x-auto">
                                        @foreach ($section->subsections as $subsection)
                                            <button
                                                id="parent-subsection-tab-{{ $subsection->id }}"
                                                type="button"
                                                role="tab"
                                                aria-controls="parent-subsection-panel-{{ $subsection->id }}"
                                                :aria-selected="activeSubsection === {{ $subsection->id }} ? 'true' : 'false'"
                                                :tabindex="activeSubsection === {{ $subsection->id }} ? 0 : -1"
                                                @click="activeSubsection = {{ $subsection->id }}"
                                                :class="activeSubsection === {{ $subsection->id }} ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-500 hover:text-slate-800'"
                                                class="shrink-0 border-b-2 px-3 py-3 text-sm font-medium transition"
                                            >{{ $subsection->title }}</button>
                                        @endforeach
                                    </div>
                                </div>

                                @foreach ($section->subsections as $subsection)
                                    <div
                                        id="parent-subsection-panel-{{ $subsection->id }}"
                                        role="tabpanel"
                                        aria-labelledby="parent-subsection-tab-{{ $subsection->id }}"
                                        x-show="activeSubsection === {{ $subsection->id }}"
                                        x-cloak
                                        class="pt-5"
                                    >
                                        <h4 class="font-semibold text-slate-800">{{ $subsection->title }}</h4>
                                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                            @foreach ($subsection->fields as $field)
                                                @include('matriculas._field', ['field' => $field])
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </section>
                    @endforeach
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('matriculas.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Cancelar</a>
                    <button class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">{{ $submission ? 'Guardar cambios' : 'Enviar ficha' }}</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
