<label class="grid gap-1.5 text-sm font-medium text-slate-700 {{ $field->type === 'textarea' ? 'sm:col-span-2' : '' }}">
    @php($currentValue = old('responses.'.$field->id, $submission?->responses[$field->id] ?? ''))
    <span>{{ $field->label }} @if ($field->is_required)<span class="text-rose-600">*</span>@endif</span>
    @if ($field->type === 'select')
        <select name="responses[{{ $field->id }}]" @required($field->is_required) class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"><option value="">Selecciona una opción</option>@foreach ($field->options ?? [] as $option)<option value="{{ $option }}" @selected($currentValue === $option)>{{ $option }}</option>@endforeach</select>
    @elseif ($field->type === 'textarea')
        <textarea name="responses[{{ $field->id }}]" rows="3" @required($field->is_required) class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ $currentValue }}</textarea>
    @else
        <input name="responses[{{ $field->id }}]" type="{{ $field->type }}" value="{{ $currentValue }}" @required($field->is_required) class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
    @endif
    @error('responses.'.$field->id)<span class="text-xs font-normal text-rose-600">{{ $message }}</span>@enderror
</label>
