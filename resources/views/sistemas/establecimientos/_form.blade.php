@php
    use App\Enums\Regimen;

    $establecimiento = $establecimiento ?? null;
    $administrador = $administrador ?? null;
    $zonaId = old('zona_id', $establecimiento?->zona_id);
    $distritoId = old('distrito_id', $establecimiento?->distrito_id);
    $circuitoId = old('circuito_id', $establecimiento?->circuito_id);
    $regimen = old('regimen', $establecimiento?->regimen);
@endphp

<div
    class="space-y-6"
    x-data="{
        zonaId: @js($zonaId === null ? '' : (string) $zonaId),
        distritoId: @js($distritoId === null ? '' : (string) $distritoId),
        circuitoId: @js($circuitoId === null ? '' : (string) $circuitoId),
        distritos: @js($distritos),
        circuitos: @js($circuitos),
        get filteredDistritos() {
            return this.distritos.filter((distrito) => String(distrito.zona_id) === String(this.zonaId));
        },
        get filteredCircuitos() {
            return this.circuitos.filter((circuito) => String(circuito.distrito_id) === String(this.distritoId));
        },
        onZonaChange() {
            this.distritoId = '';
            this.circuitoId = '';
        },
        onDistritoChange() {
            this.circuitoId = '';
        },
    }"
>
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div>
            <x-input-label for="nombre" value="Nombre" />
            <x-text-input id="nombre" class="mt-1 block w-full" type="text" name="nombre" :value="old('nombre', $establecimiento?->nombre)" required autofocus />
            <x-input-error class="mt-2" :messages="$errors->get('nombre')" />
        </div>

        <div>
            <x-input-label for="codigo_amie" value="Código AMIE" />
            <x-text-input id="codigo_amie" class="mt-1 block w-full" type="text" name="codigo_amie" :value="old('codigo_amie', $establecimiento?->codigo_amie)" required />
            <x-input-error class="mt-2" :messages="$errors->get('codigo_amie')" />
        </div>
    </div>

    <div>
        <x-input-label for="descripcion" value="Descripción" />
        <x-text-input id="descripcion" class="mt-1 block w-full" type="text" name="descripcion" :value="old('descripcion', $establecimiento?->descripcion)" required />
        <x-input-error class="mt-2" :messages="$errors->get('descripcion')" />
    </div>

    <div>
        <x-input-label for="direccion" value="Dirección" />
        <x-text-input id="direccion" class="mt-1 block w-full" type="text" name="direccion" :value="old('direccion', $establecimiento?->direccion)" required />
        <x-input-error class="mt-2" :messages="$errors->get('direccion')" />
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div>
            <x-input-label for="telefono" value="Teléfono" />
            <x-text-input id="telefono" class="mt-1 block w-full" type="text" name="telefono" :value="old('telefono', $establecimiento?->telefono)" required />
            <x-input-error class="mt-2" :messages="$errors->get('telefono')" />
        </div>

        <div>
            <x-input-label for="representante" value="Representante" />
            <x-text-input id="representante" class="mt-1 block w-full" type="text" name="representante" :value="old('representante', $establecimiento?->representante)" required />
            <x-input-error class="mt-2" :messages="$errors->get('representante')" />
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div>
            <x-input-label for="email" value="Correo" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email', $establecimiento?->email)" required />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label for="usuario" value="Usuario" />
            <x-text-input id="usuario" class="mt-1 block w-full" type="text" name="usuario" :value="old('usuario', $establecimiento?->usuario)" required />
            <x-input-error class="mt-2" :messages="$errors->get('usuario')" />
        </div>
    </div>

    <div>
        <x-input-label for="regimen" value="Régimen" />
        <select
            id="regimen"
            name="regimen"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            required
        >
            <option value="" @selected($regimen === null || $regimen === '')>Selecciona un régimen</option>
            @foreach (Regimen::cases() as $option)
                <option value="{{ $option->value }}" @selected((string) $regimen === $option->value)>
                    {{ $option->label() }}
                </option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('regimen')" />
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
        <div>
            <x-input-label for="zona_id" value="Zona" />
            <select
                id="zona_id"
                name="zona_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                required
                x-model="zonaId"
                @change="onZonaChange()"
            >
                <option value="">Selecciona una zona</option>
                @foreach ($zonas as $zona)
                    <option value="{{ $zona['id'] }}">{{ $zona['nombre'] }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('zona_id')" />
        </div>

        <div>
            <x-input-label for="distrito_id" value="Distrito" />
            <select
                id="distrito_id"
                name="distrito_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-slate-100"
                required
                x-model="distritoId"
                x-bind:disabled="zonaId === ''"
                @change="onDistritoChange()"
            >
                <option value="">Selecciona un distrito</option>
                <template x-for="distrito in filteredDistritos" :key="distrito.id">
                    <option :value="distrito.id" x-text="distrito.nombre"></option>
                </template>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('distrito_id')" />
        </div>

        <div>
            <x-input-label for="circuito_id" value="Circuito" />
            <select
                id="circuito_id"
                name="circuito_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-slate-100"
                required
                x-model="circuitoId"
                x-bind:disabled="distritoId === ''"
            >
                <option value="">Selecciona un circuito</option>
                <template x-for="circuito in filteredCircuitos" :key="circuito.id">
                    <option :value="circuito.id" x-text="circuito.nombre"></option>
                </template>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('circuito_id')" />
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div>
            <x-input-label for="logo" value="Logo" />
            <input
                id="logo"
                type="file"
                name="logo"
                accept="image/jpeg,image/png,image/webp"
                class="mt-1 block w-full text-sm text-slate-700 file:mr-4 file:rounded-md file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-xs file:font-semibold file:uppercase file:tracking-widest file:text-white hover:file:bg-slate-800"
                @required($establecimiento === null)
            />
            @if (is_string($establecimiento?->logo) && $establecimiento->logo !== '')
                <img src="{{ Storage::disk('public')->url($establecimiento->logo) }}" alt="Logo actual" class="mt-3 h-16 w-16 rounded-md object-cover">
            @endif
            <x-input-error class="mt-2" :messages="$errors->get('logo')" />
        </div>

        <div>
            <x-input-label for="grupo_amie" value="Grupo AMIE" />
            <x-text-input id="grupo_amie" class="mt-1 block w-full" type="number" name="grupo_amie" :value="old('grupo_amie', $establecimiento?->grupo_amie)" />
            <x-input-error class="mt-2" :messages="$errors->get('grupo_amie')" />
        </div>
    </div>

    <div class="space-y-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
        <div>
            <p class="text-sm font-medium text-slate-800">Usuario administrador</p>
            <p class="mt-1 text-xs text-slate-500">Este usuario entra con el rol Administrador y queda ligado a este establecimiento.</p>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <x-input-label for="admin_name" value="Nombre del administrador" />
                <x-text-input id="admin_name" class="mt-1 block w-full" type="text" name="admin_name" :value="old('admin_name', $administrador?->name)" required />
                <x-input-error class="mt-2" :messages="$errors->get('admin_name')" />
            </div>

            <div>
                <x-input-label for="admin_email" value="Correo del administrador" />
                <x-text-input id="admin_email" class="mt-1 block w-full" type="email" name="admin_email" :value="old('admin_email', $administrador?->email)" required autocomplete="username" />
                <x-input-error class="mt-2" :messages="$errors->get('admin_email')" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <x-input-label for="admin_password" :value="$administrador ? 'Nueva contraseña (opcional)' : 'Contraseña'" />
                <x-text-input id="admin_password" class="mt-1 block w-full" type="password" name="admin_password" autocomplete="new-password" :required="$administrador === null" />
                <x-input-error class="mt-2" :messages="$errors->get('admin_password')" />
            </div>

            <div>
                <x-input-label for="admin_password_confirmation" value="Confirmar contraseña" />
                <x-text-input id="admin_password_confirmation" class="mt-1 block w-full" type="password" name="admin_password_confirmation" autocomplete="new-password" :required="$administrador === null" />
            </div>
        </div>
    </div>

    <label class="flex items-center gap-2 text-sm text-slate-700">
        <input type="hidden" name="activo" value="0">
        <input
            id="activo"
            type="checkbox"
            name="activo"
            value="1"
            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
            @checked(old('activo', $establecimiento?->activo ?? 1))
        >
        Activo
    </label>
    <x-input-error class="mt-2" :messages="$errors->get('activo')" />
</div>
