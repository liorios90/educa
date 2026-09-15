@php
    $report = $report ?? null;
    $selectedRoles = collect(old('roles', $report?->roles->pluck('id')->all() ?? []))->map(fn ($id) => (int) $id);
    $visibleToAll = (bool) old('visible_to_all', $report?->visible_to_all ?? false);
    $initialSource = old('source', $report?->source ?? array_key_first($sources));
    $initialFields = collect(old('fields', $report?->fields->map(fn ($field) => [
        'key' => $field->column,
        'label' => $field->label,
        'label_x' => $field->label_x,
        'label_y' => $field->label_y,
        'value_x' => $field->value_x,
        'value_y' => $field->value_y,
    ])->values()->all() ?? []))->values()->map(fn ($field, $index) => [
        'id' => ($field['key'] ?? 'field').'-'.$index,
        'key' => $field['key'] ?? $field['column'] ?? '',
        'label' => $field['label'] ?? '',
        'label_x' => (int) ($field['label_x'] ?? 4),
        'label_y' => (int) ($field['label_y'] ?? min(8 + ($index * 14), 86)),
        'value_x' => (int) ($field['value_x'] ?? 32),
        'value_y' => (int) ($field['value_y'] ?? min(8 + ($index * 14), 86)),
    ])->all();
    $initialLayout = old('layout', $report?->layout?->value ?? \App\Enums\ReportLayout::Canvas->value);
    $initialTable = [
        'border_width' => (int) old('table_border_width', $report?->table_border_width ?? 1),
        'border_color' => old('table_border_color', $report?->table_border_color ?? '#cbd5e1'),
        'header' => (bool) old('table_header', $report?->table_header ?? true),
        'header_background' => old('table_header_background', $report?->table_header_background ?? '#f1f5f9'),
        'striped' => (bool) old('table_striped', $report?->table_striped ?? false),
        'font_size' => (int) old('table_font_size', $report?->table_font_size ?? 12),
        'cell_padding' => (int) old('table_cell_padding', $report?->table_cell_padding ?? 8),
    ];
@endphp

<div
    class="space-y-6"
    x-data="{
        sources: {{ \Illuminate\Support\Js::from($sources) }},
        source: {{ \Illuminate\Support\Js::from($initialSource) }},
        fields: {{ \Illuminate\Support\Js::from($initialFields) }},
        visibleToAll: {{ \Illuminate\Support\Js::from($visibleToAll) }},
        previousSource: {{ \Illuminate\Support\Js::from($initialSource) }},
        layout: {{ \Illuminate\Support\Js::from($initialLayout) }},
        table: {{ \Illuminate\Support\Js::from($initialTable) }},
        dragging: null,
        cellStyle() {
            return {
                border: this.table.border_width + 'px solid ' + this.table.border_color,
                padding: this.table.cell_padding + 'px',
            };
        },
        headerStyle() {
            return { ...this.cellStyle(), backgroundColor: this.table.header_background };
        },
        rowStyle(index) {
            return this.table.striped && index % 2 === 1 ? { backgroundColor: '#f8fafc' } : {};
        },
        availableGroups() {
            const fields = this.sources[this.source]?.fields ?? [];
            const groups = [];
            fields.forEach((field) => {
                let group = groups.find((item) => item.label === field.group);
                if (! group) {
                    group = { label: field.group, fields: [] };
                    groups.push(group);
                }
                group.fields.push(field);
            });
            return groups;
        },
        isSelected(key) {
            return this.fields.some((field) => field.key === key);
        },
        addField(key, label) {
            if (this.isSelected(key)) {
                return;
            }
            const index = this.fields.length;
            const row = Math.min(8 + (index * 14), 86);
            this.fields.push({
                id: key + '-' + Date.now(),
                key,
                label,
                label_x: 4,
                label_y: row,
                value_x: 32,
                value_y: row,
            });
        },
        removeField(index) {
            this.fields.splice(index, 1);
        },
        onSourceChange() {
            if (this.source === this.previousSource) {
                return;
            }
            this.fields = [];
            this.previousSource = this.source;
        },
        clamp(value) {
            return Math.max(0, Math.min(90, Math.round(value)));
        },
        startDrag(event, index, part) {
            event.preventDefault();
            const canvas = this.$refs.canvas.getBoundingClientRect();
            const field = this.fields[index];
            const x = canvas.left + ((field[part + '_x'] / 100) * canvas.width);
            const y = canvas.top + ((field[part + '_y'] / 100) * canvas.height);
            this.dragging = {
                index,
                part,
                dx: event.clientX - x,
                dy: event.clientY - y,
            };
        },
        onPointerMove(event) {
            if (! this.dragging || ! this.$refs.canvas) {
                return;
            }
            const canvas = this.$refs.canvas.getBoundingClientRect();
            const x = this.clamp(((event.clientX - this.dragging.dx - canvas.left) / canvas.width) * 100);
            const y = this.clamp(((event.clientY - this.dragging.dy - canvas.top) / canvas.height) * 100);
            this.fields[this.dragging.index][this.dragging.part + '_x'] = x;
            this.fields[this.dragging.index][this.dragging.part + '_y'] = y;
        },
        stopDrag() {
            this.dragging = null;
        }
    }"
    @pointermove.window="onPointerMove($event)"
    @pointerup.window="stopDrag()"
    @pointercancel.window="stopDrag()"
>
    <div>
        <x-input-label for="name" value="Nombre del reporte" />
        <x-text-input id="name" class="mt-1 block w-full" type="text" name="name" :value="old('name', $report?->name)" required autofocus />
        <x-input-error class="mt-2" :messages="$errors->get('name')" />
    </div>

    <div>
        <x-input-label for="source" value="Tabla" />
        <select
            id="source"
            name="source"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            x-model="source"
            @change="onSourceChange()"
            required
        >
            <template x-for="(definition, slug) in sources" :key="slug">
                <option :value="slug" x-text="definition.label" :selected="slug === source"></option>
            </template>
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('source')" />
    </div>

    <div>
        <x-input-label for="layout" value="Diseño del resultado" />
        <select
            id="layout"
            name="layout"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            x-model="layout"
        >
            @foreach (\App\Enums\ReportLayout::cases() as $case)
                <option value="{{ $case->value }}">{{ $case->label() }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-500">
            Hoja libre coloca cada dato donde quieras. Tabla de resultados muestra todos los registros en filas y columnas.
        </p>
        <x-input-error class="mt-2" :messages="$errors->get('layout')" />
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p class="mb-3 text-sm font-medium text-slate-800">Campos disponibles (tabla y relaciones)</p>
            <template x-for="group in availableGroups()" :key="group.label">
                <div class="mb-4 last:mb-0">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500" x-text="group.label"></p>
                    <ul class="flex flex-col gap-2">
                        <template x-for="field in group.fields" :key="field.key">
                            <li>
                                <button
                                    type="button"
                                    class="flex w-full items-center justify-between rounded-lg border border-slate-200 bg-white px-3 py-2 text-left text-sm text-slate-700 hover:border-indigo-300 disabled:opacity-50"
                                    :disabled="isSelected(field.key)"
                                    @click="addField(field.key, field.label)"
                                >
                                    <span x-text="field.label"></span>
                                    <span class="text-xs text-slate-400" x-text="isSelected(field.key) ? 'Añadido' : 'Añadir'"></span>
                                </button>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <p class="mb-3 text-sm font-medium text-slate-800">Descripciones</p>
            <p class="mb-3 text-xs text-slate-500">
                <span x-show="layout === 'canvas'">Edita el texto de cada descripción. La posición se ajusta en la vista previa.</span>
                <span x-show="layout === 'table'" x-cloak>Cada descripción es el título de una columna. El orden es el de esta lista.</span>
            </p>
            <x-input-error class="mb-2" :messages="$errors->get('fields')" />
            <ul class="flex flex-col gap-2">
                <template x-for="(field, index) in fields" :key="field.id">
                    <li class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                        <input type="hidden" :name="'fields[' + index + '][column]'" :value="field.key">
                        <input type="hidden" :name="'fields[' + index + '][label_x]'" :value="field.label_x">
                        <input type="hidden" :name="'fields[' + index + '][label_y]'" :value="field.label_y">
                        <input type="hidden" :name="'fields[' + index + '][value_x]'" :value="field.value_x">
                        <input type="hidden" :name="'fields[' + index + '][value_y]'" :value="field.value_y">
                        <input
                            type="text"
                            class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            :name="'fields[' + index + '][label]'"
                            x-model="field.label"
                        >
                        <button type="button" class="text-xs font-medium text-red-600 hover:text-red-500" @click="removeField(index)">Quitar</button>
                    </li>
                </template>
            </ul>
            <p x-show="fields.length === 0" class="text-sm text-slate-500">Aún no hay campos. Añádelos desde la izquierda.</p>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4" x-show="layout === 'table'" x-cloak>
        <p class="mb-3 text-sm font-medium text-slate-800">Formato de la tabla</p>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <x-input-label for="table_border_width" value="Grosor del borde (px)" />
                <x-text-input id="table_border_width" class="mt-1 block w-full" type="number" name="table_border_width" min="0" max="5" x-model.number="table.border_width" />
                <p class="mt-1 text-xs text-slate-500">0 deja la tabla sin bordes.</p>
                <x-input-error class="mt-2" :messages="$errors->get('table_border_width')" />
            </div>

            <div>
                <x-input-label for="table_border_color" value="Color del borde" />
                <input id="table_border_color" type="color" name="table_border_color" class="mt-1 h-10 w-full rounded-md border border-gray-300 shadow-sm" x-model="table.border_color">
                <x-input-error class="mt-2" :messages="$errors->get('table_border_color')" />
            </div>

            <div>
                <x-input-label for="table_header_background" value="Fondo del encabezado" />
                <input id="table_header_background" type="color" name="table_header_background" class="mt-1 h-10 w-full rounded-md border border-gray-300 shadow-sm" x-model="table.header_background">
                <x-input-error class="mt-2" :messages="$errors->get('table_header_background')" />
            </div>

            <div>
                <x-input-label for="table_font_size" value="Tamaño de letra (px)" />
                <x-text-input id="table_font_size" class="mt-1 block w-full" type="number" name="table_font_size" min="8" max="20" x-model.number="table.font_size" />
                <x-input-error class="mt-2" :messages="$errors->get('table_font_size')" />
            </div>

            <div>
                <x-input-label for="table_cell_padding" value="Espacio interior (px)" />
                <x-text-input id="table_cell_padding" class="mt-1 block w-full" type="number" name="table_cell_padding" min="0" max="24" x-model.number="table.cell_padding" />
                <x-input-error class="mt-2" :messages="$errors->get('table_cell_padding')" />
            </div>

            <div class="flex flex-col justify-center gap-2">
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="hidden" name="table_header" value="0">
                    <input type="checkbox" name="table_header" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" x-model="table.header">
                    Mostrar encabezado
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="hidden" name="table_striped" value="0">
                    <input type="checkbox" name="table_striped" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" x-model="table.striped">
                    Filas alternas
                </label>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4" x-show="layout === 'table'" x-cloak>
        <p class="mb-1 text-sm font-medium text-slate-800">Vista previa</p>
        <p class="mb-3 text-xs text-slate-500">Así se verá la tabla. Al generar el reporte se muestran los datos reales.</p>
        <div class="overflow-x-auto rounded-xl border border-slate-300 bg-white p-4">
            <p x-show="fields.length === 0" class="text-sm text-slate-400">Añade campos para formar las columnas.</p>
            <table x-show="fields.length > 0" class="w-full" style="border-collapse: collapse;" :style="{ fontSize: table.font_size + 'px' }">
                <thead x-show="table.header">
                    <tr>
                        <template x-for="field in fields" :key="'head-' + field.id">
                            <th class="text-left font-semibold" :style="headerStyle()" x-text="field.label"></th>
                        </template>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="sample in [1, 2, 3]" :key="'row-' + sample">
                        <tr :style="rowStyle(sample - 1)">
                            <template x-for="field in fields" :key="'cell-' + sample + '-' + field.id">
                                <td :style="cellStyle()" x-text="'Ejemplo ' + sample"></td>
                            </template>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4" x-show="layout === 'canvas'">
        <p class="mb-1 text-sm font-medium text-slate-800">Vista previa</p>
        <p class="mb-3 text-xs text-slate-500">Arrastra cada descripción y cada campo a cualquier lugar de la hoja.</p>
        <div
            x-ref="canvas"
            class="relative min-h-[32rem] overflow-hidden rounded-xl border border-slate-300 bg-white shadow-inner"
            :class="dragging ? 'cursor-grabbing select-none' : ''"
        >
            <p x-show="fields.length === 0" class="absolute inset-0 flex items-center justify-center text-sm text-slate-400">
                Añade campos para colocarlos aquí.
            </p>
            <template x-for="(field, index) in fields" :key="'preview-' + field.id">
                <div>
                    <button
                        type="button"
                        class="absolute z-10 max-w-[40%] cursor-grab touch-none rounded border border-amber-200 bg-amber-50 px-2 py-1 text-left text-xs font-semibold text-amber-900 shadow-sm hover:border-amber-400"
                        :style="{ left: field.label_x + '%', top: field.label_y + '%' }"
                        @pointerdown="startDrag($event, index, 'label')"
                        title="Mover descripción"
                    >
                        <span class="mr-1 text-[10px] uppercase tracking-wide text-amber-600">Desc.</span>
                        <span x-text="field.label"></span>
                    </button>
                    <button
                        type="button"
                        class="absolute z-10 max-w-[50%] cursor-grab touch-none rounded border border-indigo-200 bg-indigo-50 px-2 py-1 text-left text-sm text-indigo-950 shadow-sm hover:border-indigo-400"
                        :style="{ left: field.value_x + '%', top: field.value_y + '%' }"
                        @pointerdown="startDrag($event, index, 'value')"
                        title="Mover campo"
                    >
                        <span class="mr-1 text-[10px] uppercase tracking-wide text-indigo-500">Campo</span>
                        <span x-text="'Ejemplo · ' + field.key"></span>
                    </button>
                </div>
            </template>
        </div>
    </div>

    <label class="flex items-center gap-2 text-sm text-slate-700">
        <input type="hidden" name="is_active" value="0">
        <input id="is_active" type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked(old('is_active', $report?->is_active ?? true))>
        Disponible para generar
    </label>

    <label class="flex items-center gap-2 text-sm text-slate-700">
        <input type="hidden" name="visible_to_all" value="0">
        <input
            id="visible_to_all"
            type="checkbox"
            name="visible_to_all"
            value="1"
            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
            @checked($visibleToAll)
            x-model="visibleToAll"
        >
        Visible para todos los usuarios autenticados
    </label>

    <div x-show="! visibleToAll" x-cloak>
        <p class="mb-2 text-sm font-medium text-gray-700">Quién puede generar este reporte</p>
        <div class="flex flex-col gap-2">
            @foreach ($roles as $role)
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="roles[]" value="{{ $role->id }}" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked($selectedRoles->contains($role->id))>
                    {{ \App\Enums\Role::tryFrom($role->name)?->label() ?? $role->name }}
                </label>
            @endforeach
        </div>
        <x-input-error class="mt-2" :messages="$errors->get('roles')" />
    </div>
</div>
