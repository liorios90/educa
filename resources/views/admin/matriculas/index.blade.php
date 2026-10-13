<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <a href="{{ route('Admin.matriculas.index') }}" class="text-sm font-medium text-indigo-700 hover:text-indigo-900">← Formularios de matrícula</a>
                <h2 class="mt-1 text-xl font-semibold leading-tight text-slate-800">Diseño del formulario de matrícula</h2>
                <p class="mt-1 text-sm text-slate-500">Periodo: {{ $periodo?->nombre ?? ($form->periodo_inicio?->format('Y-m-d').' – '.$form->periodo_fin?->format('Y-m-d')) }} · Organiza la información que completarán las familias.</p>
            </div>
            <a href="{{ route('Admin.matriculas.submissions', $form) }}" class="rounded-lg border border-indigo-200 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">Ver {{ $submissionsCount }} respuestas</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto flex max-w-6xl flex-col gap-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <p class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">Los cambios se guardaron correctamente.</p>
            @endif
            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>
            @endif

            @if ($form === null)
                <section class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center text-slate-600">Selecciona una oferta y un periodo escolar activo para administrar su formulario de matrícula.</section>
            @else
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <form method="POST" action="{{ route('Admin.matriculas.forms.update', $form) }}" class="flex flex-wrap items-end gap-3">
                            @csrf @method('PATCH')
                            <label class="grid gap-1 text-sm font-medium text-slate-700">Nombre del formulario
                                <input name="title" value="{{ $form->title }}" required maxlength="120" class="rounded-lg border-slate-300 text-sm">
                            </label>
                            <button class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700">Guardar nombre</button>
                        </form>
                        <p class="mt-2 text-sm text-slate-500">Organiza las secciones y los campos que completarán las familias.</p>
                    </div>
                    <form method="POST" action="{{ route('Admin.matriculas.sections.store', $form) }}" class="flex flex-wrap items-end gap-3">
                        @csrf
                        <label class="grid gap-1 text-sm font-medium text-slate-700">Nueva sección
                            <input name="title" required maxlength="120" placeholder="Ej. Información médica" class="rounded-lg border-slate-300 text-sm">
                        </label>
                        <button class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Añadir sección</button>
                    </form>
                </div>
            </section>

            <section x-data="{ activeSection: {{ (int) old('active_section_id', session('activeSectionId', $form->sections->first()?->id ?? 0)) }} }" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto border-b border-slate-200 bg-slate-50 px-4 pt-3">
                    <div role="tablist" aria-label="Secciones del formulario" class="flex min-w-max gap-2">
                        @foreach ($form->sections as $section)
                            <button
                                id="section-tab-{{ $section->id }}"
                                type="button"
                                role="tab"
                                aria-controls="section-panel-{{ $section->id }}"
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
                    <div
                        id="section-panel-{{ $section->id }}"
                        role="tabpanel"
                        aria-labelledby="section-tab-{{ $section->id }}"
                        x-show="activeSection === {{ $section->id }}"
                        x-cloak
                        x-data="{ activeSubsection: {{ (int) ((int) old('active_section_id', session('activeSectionId', 0)) === $section->id ? old('active_subsection_id', session('activeSubsectionId', $section->subsections->first()?->id ?? 0)) : ($section->subsections->first()?->id ?? 0)) }} }"
                        class="p-5 sm:p-6"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">{{ $section->title }}</h3>
                                <p class="mt-1 text-sm text-slate-500">Administra las subsecciones y los campos de esta sección.</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <form method="POST" action="{{ route('Admin.matriculas.sections.store', $form) }}" class="flex items-center gap-2">
                                    @csrf
                                    <input type="hidden" name="parent_id" value="{{ $section->id }}">
                                    <input type="hidden" name="active_section_id" value="{{ $section->id }}">
                                    @if ($section->subsections->isNotEmpty())<input type="hidden" name="active_subsection_id" value="{{ old('active_subsection_id', $section->subsections->first()->id) }}">@endif
                                    <input name="title" required maxlength="120" placeholder="Nueva subsección" class="w-48 rounded-lg border-slate-300 text-sm">
                                    <button class="text-sm font-semibold text-indigo-700 hover:text-indigo-900">Añadir</button>
                                </form>
                                <form method="POST" action="{{ route('Admin.matriculas.sections.destroy', $section) }}" onsubmit="return confirm('¿Eliminar esta sección y su contenido?');">
                                    @csrf @method('DELETE')
                                    <button class="text-sm font-medium text-red-600 hover:text-red-800">Eliminar sección</button>
                                </form>
                            </div>
                        </div>

                        <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <h4 class="font-semibold text-slate-800">Campos directos de esta sección</h4>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @forelse ($section->fields as $field)
                                    <form method="POST" action="{{ route('Admin.matriculas.fields.destroy', $field) }}" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-700">
                                        @csrf @method('DELETE') {{ $field->label }} @if ($field->is_required)<span class="text-rose-600">*</span>@endif <button aria-label="Eliminar {{ $field->label }}" class="font-bold text-slate-400 hover:text-red-600">×</button>
                                    </form>
                                @empty
                                    <p class="text-sm text-slate-500">Aún no hay campos directos en esta sección.</p>
                                @endforelse
                            </div>
                            @include('admin.matriculas._field-form', [
                                'targetSection' => $section,
                                'targetForm' => $form,
                                'activeSectionId' => $section->id,
                                'activeSubsectionId' => null,
                            ])
                        </div>

                        @if ($section->subsections->isNotEmpty())
                            <div class="mt-6 border-b border-slate-200">
                                <div role="tablist" aria-label="Subsecciones de {{ $section->title }}" class="flex gap-2 overflow-x-auto">
                                    @foreach ($section->subsections as $subsection)
                                        <button
                                            id="subsection-tab-{{ $subsection->id }}"
                                            type="button"
                                            role="tab"
                                            aria-controls="subsection-panel-{{ $subsection->id }}"
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
                                    id="subsection-panel-{{ $subsection->id }}"
                                    role="tabpanel"
                                    aria-labelledby="subsection-tab-{{ $subsection->id }}"
                                    x-show="activeSubsection === {{ $subsection->id }}"
                                    x-cloak
                                    class="pt-5"
                                >
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <h4 class="font-semibold text-slate-800">{{ $subsection->title }}</h4>
                                        <form method="POST" action="{{ route('Admin.matriculas.sections.destroy', $subsection) }}" onsubmit="return confirm('¿Eliminar esta subsección y sus campos?');">
                                            @csrf @method('DELETE')
                                            <button class="text-sm font-medium text-red-600 hover:text-red-800">Eliminar subsección</button>
                                        </form>
                                    </div>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @forelse ($subsection->fields as $field)
                                            <form method="POST" action="{{ route('Admin.matriculas.fields.destroy', $field) }}" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm text-slate-700">
                                                @csrf @method('DELETE') {{ $field->label }} @if ($field->is_required)<span class="text-rose-600">*</span>@endif <button aria-label="Eliminar {{ $field->label }}" class="font-bold text-slate-400 hover:text-red-600">×</button>
                                            </form>
                                        @empty
                                            <p class="text-sm text-slate-500">Todavía no hay campos en esta subsección.</p>
                                        @endforelse
                                    </div>
                                    @include('admin.matriculas._field-form', [
                                        'targetSection' => $subsection,
                                        'targetForm' => $form,
                                        'activeSectionId' => $section->id,
                                        'activeSubsectionId' => $subsection->id,
                                    ])
                                </div>
                            @endforeach
                        @else
                            <p class="mt-6 rounded-xl border border-dashed border-slate-300 p-5 text-center text-sm text-slate-500">Esta sección todavía no tiene subsecciones. Puedes agregar campos directamente arriba o crear una subsección.</p>
                        @endif
                    </div>
                @endforeach
            </section>
            @endif
        </div>
    </div>
</x-app-layout>
