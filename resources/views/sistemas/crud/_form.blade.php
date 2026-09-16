@php
    $record = $record ?? null;
    $options = $options ?? [];
@endphp

<div class="space-y-6">
    @foreach ($definition->fields as $field)
        @if ($field->type === 'boolean')
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="hidden" name="{{ $field->name }}" value="0">
                <input
                    id="{{ $field->name }}"
                    type="checkbox"
                    name="{{ $field->name }}"
                    value="1"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                    @checked(old($field->name, $record?->getAttribute($field->name)))
                >
                {{ $field->label }}
            </label>
        @elseif ($field->type === 'textarea')
            <div>
                <x-input-label :for="$field->name" :value="$field->label" />
                <textarea
                    id="{{ $field->name }}"
                    name="{{ $field->name }}"
                    rows="3"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >{{ old($field->name, $record?->getAttribute($field->name)) }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get($field->name)" />
            </div>
        @elseif ($field->type === 'select')
            <div>
                <x-input-label :for="$field->name" :value="$field->label" />
                <select
                    id="{{ $field->name }}"
                    name="{{ $field->name }}"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="">Selecciona {{ strtolower($field->label) }}</option>
                    @foreach ($options[$field->name] ?? [] as $option)
                        <option
                            value="{{ $option['id'] }}"
                            @selected((string) old($field->name, $record?->getAttribute($field->name)) === (string) $option['id'])
                        >
                            {{ $option['label'] }}
                        </option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get($field->name)" />
            </div>
        @else
            <div>
                <x-input-label :for="$field->name" :value="$field->label" />
                <x-text-input
                    :id="$field->name"
                    class="mt-1 block w-full"
                    :type="$field->type === 'integer' ? 'number' : 'text'"
                    :name="$field->name"
                    :value="old($field->name, $record?->getAttribute($field->name))"
                    :autofocus="$loop->first"
                />
                <x-input-error class="mt-2" :messages="$errors->get($field->name)" />
            </div>
        @endif
    @endforeach
</div>
