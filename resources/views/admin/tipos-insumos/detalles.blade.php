<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-slate-800">Detalle de tipo de Insumo</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $tipoInsumo->nombre }} · {{ \App\Enums\TipoCalificacion::tryFrom($tipoInsumo->tipo_calificacion)?->label() }}</p>
            </div>
            <a href="{{ route('Admin.tipos-insumos.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Volver a tipos de Insumo</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8" x-data="{
            total: @js($totalPorcentajeActivo),
            recalculate() {
                const rows = [...this.$root.querySelectorAll('[data-detalle-row]')];
                this.total = rows.reduce((sum, row) => {
                    const percentage = row.querySelector('[data-porcentaje]');
                    const active = row.querySelector('[data-activo]');
                    return sum + (percentage && active?.checked ? (Number(percentage.value) || 0) : 0);
                }, 0);
                const newPercentage = this.$root.querySelector('[data-nuevo-porcentaje]');
                const newActive = this.$root.querySelector('[data-nuevo-activo]');
                if (newPercentage && newActive?.checked) {
                    this.total += Number(newPercentage.value) || 0;
                }
            }
        }" x-init="recalculate()">
            @if (session('status') === 'tipo-insumo-detalle-created')
                <p class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">Detalle creado correctamente.</p>
            @elseif (session('status') === 'tipo-insumo-detalle-updated')
                <p class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">Detalle actualizado correctamente.</p>
            @elseif (session('status') === 'tipo-insumo-detalle-deleted')
                <p class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">Detalle eliminado correctamente.</p>
            @endif

            @if ($tipoInsumo->tipo_calificacion === \App\Enums\TipoCalificacion::Cuantitativa->value)
                <div class="rounded-xl border px-4 py-3 text-sm" :class="total === 100 ? 'border-green-200 bg-green-50 text-green-800' : 'border-amber-200 bg-amber-50 text-amber-900'" aria-live="polite">
                    <p class="font-semibold">Porcentaje activo: <span x-text="total.toFixed(2)"></span>% de 100%</p>
                    <p x-show="total < 100">Falta <span x-text="(100 - total).toFixed(2)">{{ number_format(max(0, 100 - $totalPorcentajeActivo), 2) }}</span>% para completar 100%.</p>
                    <p x-show="total > 100">El porcentaje supera el 100% por <span x-text="(total - 100).toFixed(2)">{{ number_format(max(0, $totalPorcentajeActivo - 100), 2) }}</span>%.</p>
                    <p x-show="total === 100">La suma de porcentajes activos está completa.</p>
                </div>
            @endif

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
                    <h3 class="font-semibold text-slate-900">Nuevo detalle</h3>
                    <p class="mt-1 text-sm text-slate-500">Completa los campos para agregar un registro al tipo de Insumo.</p>
                </div>
                <form method="POST" action="{{ route('Admin.tipos-insumos.detalles.store', $tipoInsumo) }}" class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2 xl:grid-cols-5">
                    @csrf
                    <div>
                        <x-input-label for="nuevo_nombre" value="Nombre" />
                        <x-text-input id="nuevo_nombre" name="nombre" required maxlength="255" class="mt-1 block w-full" :value="old('nombre')" />
                        <x-input-error class="mt-1" :messages="$errors->get('nombre')" />
                    </div>
                    <div>
                        <x-input-label for="nuevo_descripcion" value="Descripción" />
                        <x-text-input id="nuevo_descripcion" name="descripcion" class="mt-1 block w-full" :value="old('descripcion')" />
                        <x-input-error class="mt-1" :messages="$errors->get('descripcion')" />
                    </div>
                    @if ($tipoInsumo->tipo_calificacion === \App\Enums\TipoCalificacion::Cuantitativa->value)
                        <div>
                            <x-input-label for="nuevo_porcentaje" value="Porcentaje" />
                            <x-text-input id="nuevo_porcentaje" name="porcentaje" type="number" min="0" max="100" step="0.01" required data-nuevo-porcentaje x-on:input="recalculate()" class="mt-1 block w-full" :value="old('porcentaje')" />
                            <x-input-error class="mt-1" :messages="$errors->get('porcentaje')" />
                        </div>
                    @else
                        <div>
                            <x-input-label for="nuevo_valor" value="Valor" />
                            <x-text-input id="nuevo_valor" name="valor" required maxlength="100" class="mt-1 block w-full" :value="old('valor')" />
                            <x-input-error class="mt-1" :messages="$errors->get('valor')" />
                        </div>
                    @endif
                    <div class="flex items-center gap-2 self-end pb-2">
                        <input type="hidden" name="activo" value="0">
                        <input id="nuevo_activo" type="checkbox" name="activo" value="1" checked @if ($tipoInsumo->tipo_calificacion === \App\Enums\TipoCalificacion::Cuantitativa->value) data-nuevo-activo x-on:change="recalculate()" @endif class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <x-input-label for="nuevo_activo" value="Activo" />
                    </div>
                    <div class="flex items-center xl:justify-end">
                        <x-primary-button>Agregar detalle</x-primary-button>
                    </div>
                </form>
            </section>

            <section class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h3 class="font-semibold text-slate-900">Detalles creados</h3>
                </div>
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-4 py-3 font-medium">Nombre</th>
                            <th class="px-4 py-3 font-medium">Descripción</th>
                            <th class="px-4 py-3 font-medium">{{ $tipoInsumo->tipo_calificacion === \App\Enums\TipoCalificacion::Cuantitativa->value ? 'Porcentaje' : 'Valor' }}</th>
                            <th class="px-4 py-3 font-medium">Activo</th>
                            <th class="px-4 py-3 font-medium">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        @forelse ($tipoInsumo->detalles as $detalle)
                            <tr data-detalle-row>
                                <td colspan="5" class="p-0">
                                    <form method="POST" action="{{ route('Admin.tipos-insumos.detalles.update', [$tipoInsumo, $detalle]) }}" class="grid grid-cols-1 gap-3 p-4 md:grid-cols-5 md:items-end">
                                        @csrf
                                        @method('patch')
                                        <div>
                                            <x-input-label :for="'detalle_nombre_'.$detalle->id" value="Nombre" class="md:hidden" />
                                            <input id="{{ 'detalle_nombre_'.$detalle->id }}" name="nombre" required maxlength="255" value="{{ $detalle->nombre }}" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>
                                        <div>
                                            <x-input-label :for="'detalle_descripcion_'.$detalle->id" value="Descripción" class="md:hidden" />
                                            <input id="{{ 'detalle_descripcion_'.$detalle->id }}" name="descripcion" value="{{ $detalle->descripcion }}" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>
                                        <div>
                                            <x-input-label :for="'detalle_valor_'.$detalle->id" :value="$tipoInsumo->tipo_calificacion === \App\Enums\TipoCalificacion::Cuantitativa->value ? 'Porcentaje' : 'Valor'" class="md:hidden" />
                                            @if ($tipoInsumo->tipo_calificacion === \App\Enums\TipoCalificacion::Cuantitativa->value)
                                                <input id="{{ 'detalle_valor_'.$detalle->id }}" data-porcentaje name="porcentaje" type="number" min="0" max="100" step="0.01" required value="{{ $detalle->porcentaje }}" x-on:input="recalculate()" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            @else
                                                <input id="{{ 'detalle_valor_'.$detalle->id }}" name="valor" required maxlength="100" value="{{ $detalle->valor }}" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <input type="hidden" name="activo" value="0">
                                            <input id="{{ 'detalle_activo_'.$detalle->id }}" data-activo type="checkbox" name="activo" value="1" @checked($detalle->activo) @if ($tipoInsumo->tipo_calificacion === \App\Enums\TipoCalificacion::Cuantitativa->value) x-on:change="recalculate()" @endif class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                            <x-input-label :for="'detalle_activo_'.$detalle->id" value="Activo" />
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <button type="submit" class="font-medium text-indigo-600 hover:text-indigo-800">Guardar</button>
                                            <button type="submit" form="eliminar-detalle-{{ $detalle->id }}" class="font-medium text-red-600 hover:text-red-800" onclick="return confirm('¿Eliminar este detalle?')">Eliminar</button>
                                        </div>
                                    </form>
                                    <form id="eliminar-detalle-{{ $detalle->id }}" method="POST" action="{{ route('Admin.tipos-insumos.detalles.destroy', [$tipoInsumo, $detalle]) }}" class="hidden">
                                        @csrf
                                        @method('delete')
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-8 text-center text-slate-500">Aún no hay detalles para este tipo de Insumo.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
        </div>
    </div>
</x-app-layout>
