@php
    $padre = $padre ?? null;
    $persona = $persona ?? null;
    $cuenta = $cuenta ?? null;
    $nacionalidadId = old('nacionalidad_id', $persona?->nacionalidad_id);
    $provinciaId = old('provincia_id', $persona?->provincia_id);
    $tipoIdentificacionId = old('tipo_identificacion_id', $persona?->tipo_identificacion_id);
    $generoId = old('genero_id', $persona?->genero_id);
    $estadoCivilId = old('estado_civil_id', $padre?->estado_civil_id);
@endphp

<div
    class="space-y-6"
    x-data="{
        nacionalidadId: @js($nacionalidadId === null ? '' : (string) $nacionalidadId),
        provinciaId: @js($provinciaId === null ? '' : (string) $provinciaId),
        provincias: @js($provincias),
        get filteredProvincias() {
            return this.provincias.filter((provincia) => String(provincia.pais_id) === String(this.nacionalidadId));
        },
        onPaisChange() {
            this.provinciaId = '';
        },
    }"
>
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div>
            <x-input-label for="tipo_identificacion_id" value="Tipo de identificación" />
            <select
                id="tipo_identificacion_id"
                name="tipo_identificacion_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                required
            >
                <option value="">Selecciona un tipo</option>
                @foreach ($tiposIdentificacion as $id => $label)
                    <option value="{{ $id }}" @selected((string) $tipoIdentificacionId === (string) $id)>{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('tipo_identificacion_id')" />
        </div>

        <div>
            <x-input-label for="identificacion" value="Identificación" />
            <x-text-input id="identificacion" class="mt-1 block w-full" type="text" name="identificacion" :value="old('identificacion', $persona?->identificacion)" required autofocus />
            <x-input-error class="mt-2" :messages="$errors->get('identificacion')" />
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div>
            <x-input-label for="nombres" value="Nombres" />
            <x-text-input id="nombres" class="mt-1 block w-full" type="text" name="nombres" :value="old('nombres', $persona?->nombres)" required />
            <x-input-error class="mt-2" :messages="$errors->get('nombres')" />
        </div>

        <div>
            <x-input-label for="apellidos" value="Apellidos" />
            <x-text-input id="apellidos" class="mt-1 block w-full" type="text" name="apellidos" :value="old('apellidos', $persona?->apellidos)" required />
            <x-input-error class="mt-2" :messages="$errors->get('apellidos')" />
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div>
            <x-input-label for="genero_id" value="Género" />
            <select
                id="genero_id"
                name="genero_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                required
            >
                <option value="">Selecciona un género</option>
                @foreach ($generos as $id => $label)
                    <option value="{{ $id }}" @selected((string) $generoId === (string) $id)>{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('genero_id')" />
        </div>

        <div>
            <x-input-label for="fecha_nacimiento" value="Fecha de nacimiento" />
            <x-text-input id="fecha_nacimiento" class="mt-1 block w-full" type="date" name="fecha_nacimiento" :value="old('fecha_nacimiento', $persona?->fecha_nacimiento?->format('Y-m-d'))" />
            <x-input-error class="mt-2" :messages="$errors->get('fecha_nacimiento')" />
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div>
            <x-input-label for="nacionalidad_id" value="Nacionalidad" />
            <select
                id="nacionalidad_id"
                name="nacionalidad_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                required
                x-model="nacionalidadId"
                @change="onPaisChange()"
            >
                <option value="">Selecciona un país</option>
                @foreach ($paises as $pais)
                    <option value="{{ $pais['id'] }}">{{ $pais['nombre'] }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('nacionalidad_id')" />
        </div>

        <div>
            <x-input-label for="provincia_id" value="Provincia" />
            <select
                id="provincia_id"
                name="provincia_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                required
                x-model="provinciaId"
            >
                <option value="">Selecciona una provincia</option>
                <template x-for="provincia in filteredProvincias" :key="provincia.id">
                    <option :value="provincia.id" x-text="provincia.nombre" :selected="String(provincia.id) === String(provinciaId)"></option>
                </template>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('provincia_id')" />
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div>
            <x-input-label for="ciudad_nacimiento" value="Ciudad de nacimiento" />
            <x-text-input id="ciudad_nacimiento" class="mt-1 block w-full" type="text" name="ciudad_nacimiento" :value="old('ciudad_nacimiento', $persona?->ciudad_nacimiento)" required />
            <x-input-error class="mt-2" :messages="$errors->get('ciudad_nacimiento')" />
        </div>

        <div>
            <x-input-label for="parroquia" value="Parroquia" />
            <x-text-input id="parroquia" class="mt-1 block w-full" type="text" name="parroquia" :value="old('parroquia', $persona?->parroquia)" required />
            <x-input-error class="mt-2" :messages="$errors->get('parroquia')" />
        </div>
    </div>

    <div>
        <x-input-label for="direccion" value="Dirección" />
        <x-text-input id="direccion" class="mt-1 block w-full" type="text" name="direccion" :value="old('direccion', $persona?->direccion)" required />
        <x-input-error class="mt-2" :messages="$errors->get('direccion')" />
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div>
            <x-input-label for="telefono1" value="Teléfono 1" />
            <x-text-input id="telefono1" class="mt-1 block w-full" type="text" name="telefono1" :value="old('telefono1', $persona?->telefono1)" required />
            <x-input-error class="mt-2" :messages="$errors->get('telefono1')" />
        </div>

        <div>
            <x-input-label for="telefono2" value="Teléfono 2" />
            <x-text-input id="telefono2" class="mt-1 block w-full" type="text" name="telefono2" :value="old('telefono2', $persona?->telefono2)" required />
            <x-input-error class="mt-2" :messages="$errors->get('telefono2')" />
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div>
            <x-input-label for="estado_civil_id" value="Estado civil" />
            <select
                id="estado_civil_id"
                name="estado_civil_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                required
            >
                <option value="">Selecciona un estado civil</option>
                @foreach ($estadosCiviles as $estado)
                    <option value="{{ $estado }}" @selected((string) $estadoCivilId === $estado)>{{ $estado }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('estado_civil_id')" />
        </div>

        <div>
            <x-input-label for="titulo" value="Título" />
            <x-text-input id="titulo" class="mt-1 block w-full" type="text" name="titulo" :value="old('titulo', $padre?->titulo)" required />
            <x-input-error class="mt-2" :messages="$errors->get('titulo')" />
        </div>
    </div>

    <div>
        <x-input-label for="lote" value="Lote" />
        <x-text-input id="lote" class="mt-1 block w-full" type="number" name="lote" :value="old('lote', $persona?->lote)" />
        <x-input-error class="mt-2" :messages="$errors->get('lote')" />
    </div>

    <div class="space-y-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
        <div>
            <p class="text-sm font-medium text-slate-800">Usuario de acceso</p>
            <p class="mt-1 text-xs text-slate-500">Se crea una cuenta con rol Padre para que pueda iniciar sesión.</p>
        </div>

        <div>
            <x-input-label for="email" value="Correo" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email', $cuenta?->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <x-input-label for="password" :value="$cuenta ? 'Nueva contraseña (opcional)' : 'Contraseña'" />
                <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" autocomplete="new-password" :required="$cuenta === null" />
                <x-input-error class="mt-2" :messages="$errors->get('password')" />
            </div>

            <div>
                <x-input-label for="password_confirmation" value="Confirmar contraseña" />
                <x-text-input id="password_confirmation" class="mt-1 block w-full" type="password" name="password_confirmation" autocomplete="new-password" :required="$cuenta === null" />
            </div>
        </div>
    </div>

    <div class="flex flex-col gap-3">
        <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="hidden" name="vive_con_estudiante" value="0">
            <input
                id="vive_con_estudiante"
                type="checkbox"
                name="vive_con_estudiante"
                value="1"
                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                @checked(old('vive_con_estudiante', $padre?->vive_con_estudiante ?? 1))
            >
            Vive con el estudiante
        </label>
        <x-input-error class="mt-2" :messages="$errors->get('vive_con_estudiante')" />

        <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="hidden" name="activo" value="0">
            <input
                id="activo"
                type="checkbox"
                name="activo"
                value="1"
                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                @checked(old('activo', $padre?->activo ?? 1))
            >
            Activo
        </label>
        <x-input-error class="mt-2" :messages="$errors->get('activo')" />
    </div>
</div>
