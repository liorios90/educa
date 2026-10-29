@props([
    'name' => 'descripcion',
    'value' => '',
    'id' => 'descripcion',
])

<div
    class="rounded-md border border-slate-300 shadow-sm focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500"
    x-data="{
        html: @js($value),
        aplicar(comando, valor = null) {
            this.$refs.editor.focus();
            document.execCommand(comando, false, valor);
            this.html = this.$refs.editor.innerHTML;
        },
        enlazar() {
            const url = window.prompt('Enlace');

            if (url) {
                this.aplicar('createLink', url);
            }
        },
    }"
>
    <div class="flex flex-wrap gap-1 border-b border-slate-200 bg-slate-50 px-2 py-1.5">
        <button type="button" class="rounded px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-white" x-on:click="aplicar('bold')" title="Negrita">N</button>
        <button type="button" class="rounded px-2 py-1 text-xs italic text-slate-700 hover:bg-white" x-on:click="aplicar('italic')" title="Cursiva">C</button>
        <button type="button" class="rounded px-2 py-1 text-xs underline text-slate-700 hover:bg-white" x-on:click="aplicar('underline')" title="Subrayado">S</button>
        <button type="button" class="rounded px-2 py-1 text-xs text-slate-700 hover:bg-white" x-on:click="aplicar('insertUnorderedList')" title="Lista">• Lista</button>
        <button type="button" class="rounded px-2 py-1 text-xs text-slate-700 hover:bg-white" x-on:click="aplicar('insertOrderedList')" title="Lista numerada">1. Lista</button>
        <button type="button" class="rounded px-2 py-1 text-xs text-slate-700 hover:bg-white" x-on:click="enlazar()" title="Enlace">Enlace</button>
    </div>
    <div
        id="{{ $id }}-editor"
        x-ref="editor"
        class="h-40 min-h-40 overflow-y-auto px-3 py-2 text-sm leading-6 text-slate-800 focus:outline-none"
        contenteditable="true"
        x-init="$refs.editor.innerHTML = html"
        x-on:input="html = $refs.editor.innerHTML"
    ></div>
    <input type="hidden" name="{{ $name }}" id="{{ $id }}" :value="html">
</div>
