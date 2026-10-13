<form method="POST" action="{{ route('Admin.matriculas.fields.store', [$targetForm, $targetSection]) }}" class="mt-4 grid gap-3 border-t border-slate-200 pt-4 md:grid-cols-5">
    @csrf
    <input type="hidden" name="active_section_id" value="{{ $activeSectionId }}">
    @if ($activeSubsectionId)<input type="hidden" name="active_subsection_id" value="{{ $activeSubsectionId }}">@endif
    <label class="grid gap-1 text-xs font-semibold text-slate-600 md:col-span-2">Nombre del campo<input name="label" required maxlength="120" placeholder="Ej. Alergias" class="rounded-lg border-slate-300 text-sm font-normal"></label>
    <label class="grid gap-1 text-xs font-semibold text-slate-600">Tipo
        <select name="type" class="rounded-lg border-slate-300 text-sm font-normal"><option value="text">Texto</option><option value="textarea">Texto largo</option><option value="email">Correo</option><option value="tel">Teléfono</option><option value="date">Fecha</option><option value="select">Lista</option></select>
    </label>
    <label class="grid gap-1 text-xs font-semibold text-slate-600">Opciones (una por línea)<textarea name="options" rows="1" placeholder="Solo para lista" class="rounded-lg border-slate-300 text-sm font-normal"></textarea></label>
    <div class="flex items-end gap-3"><label class="flex items-center gap-2 pb-2 text-sm text-slate-600"><input type="checkbox" name="is_required" value="1" class="rounded border-slate-300 text-indigo-600">Obligatorio</label><button class="rounded-lg bg-slate-800 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-700">Añadir campo</button></div>
</form>
