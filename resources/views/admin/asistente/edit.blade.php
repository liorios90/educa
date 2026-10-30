<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">
                Asistente
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                {{ $establecimiento->nombre }}
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-xl px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'asistente-updated')
                <p class="mb-4 text-sm font-medium text-green-700">La configuración del asistente se guardó correctamente.</p>
            @endif

            <form
                method="POST"
                action="{{ route('Admin.asistente.update') }}"
                class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
                x-data="{ proveedor: @js(old('asistente_proveedor', $establecimiento->proveedorAsistente()->value)) }"
            >
                @csrf
                @method('PUT')

                <p class="text-sm text-slate-600">
                    Cada instituto usa su propia clave. No se comparte con otros establecimientos.
                </p>

                <div>
                    <x-input-label for="asistente_proveedor" value="Proveedor" />
                    <select
                        id="asistente_proveedor"
                        name="asistente_proveedor"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        x-model="proveedor"
                        required
                    >
                        @foreach (\App\Enums\AsistenteProveedor::cases() as $proveedor)
                            <option
                                value="{{ $proveedor->value }}"
                                @selected(old('asistente_proveedor', $establecimiento->proveedorAsistente()->value) === $proveedor->value)
                            >
                                {{ $proveedor->label() }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('asistente_proveedor')" />
                </div>

                <div x-show="proveedor === 'compatible'" x-cloak>
                    <x-input-label for="asistente_base_url" value="URL base" />
                    <x-text-input
                        id="asistente_base_url"
                        name="asistente_base_url"
                        type="text"
                        class="mt-1 block w-full"
                        :value="old('asistente_base_url', $establecimiento->asistente_base_url)"
                        placeholder="https://api.groq.com/openai/v1"
                    />
                    <p class="mt-2 text-sm text-slate-500">
                        Es la base del servicio, por ejemplo https://api.groq.com/openai/v1, sin /chat/completions. Sirve para Groq, OpenRouter, Ollama u otra API con el mismo formato.
                    </p>
                    <x-input-error class="mt-2" :messages="$errors->get('asistente_base_url')" />
                </div>

                <div>
                    <x-input-label for="gemini_api_key" value="Clave" />
                    <x-text-input
                        id="gemini_api_key"
                        name="gemini_api_key"
                        type="password"
                        class="mt-1 block w-full"
                        autocomplete="new-password"
                    />
                    <p class="mt-2 text-sm text-slate-500">
                        @if ($establecimiento->tieneCredencialesGemini())
                            Hay una clave guardada. Déjala en blanco para conservarla.
                        @else
                            Aún no hay clave. El asistente no funcionará hasta que la ingreses.
                        @endif
                    </p>
                    <x-input-error class="mt-2" :messages="$errors->get('gemini_api_key')" />
                </div>

                <div>
                    <x-input-label for="gemini_model" value="Modelo" />
                    <x-text-input
                        id="gemini_model"
                        name="gemini_model"
                        type="text"
                        class="mt-1 block w-full"
                        :value="old('gemini_model', $establecimiento->modeloGemini())"
                        required
                    />
                    <x-input-error class="mt-2" :messages="$errors->get('gemini_model')" />
                </div>

                <div>
                    <x-input-label for="gemini_preguntas_limite" value="Preguntas por mes" />
                    <x-text-input
                        id="gemini_preguntas_limite"
                        name="gemini_preguntas_limite"
                        type="number"
                        min="1"
                        max="1000000"
                        class="mt-1 block w-full"
                        :value="old('gemini_preguntas_limite', $establecimiento->gemini_preguntas_limite)"
                        required
                    />
                    <p class="mt-2 text-sm text-slate-500">Cupo mensual compartido por docentes y secretaría.</p>
                    <x-input-error class="mt-2" :messages="$errors->get('gemini_preguntas_limite')" />
                </div>

                <div>
                    <x-input-label for="gemini_tokens_limite" value="Tokens por mes" />
                    <x-text-input
                        id="gemini_tokens_limite"
                        name="gemini_tokens_limite"
                        type="number"
                        min="1"
                        max="100000000"
                        class="mt-1 block w-full"
                        :value="old('gemini_tokens_limite', $establecimiento->gemini_tokens_limite)"
                        required
                    />
                    <x-input-error class="mt-2" :messages="$errors->get('gemini_tokens_limite')" />
                </div>

                <x-primary-button>Guardar configuración</x-primary-button>
            </form>
        </div>
    </div>
</x-app-layout>
