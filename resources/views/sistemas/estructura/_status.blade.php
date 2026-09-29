@if (session('status') === 'nivel-created')
    <p class="mb-4 text-sm font-medium text-green-700">Nivel creado correctamente. Ya puedes agregar subniveles.</p>
@endif
@if (session('status') === 'nivel-updated')
    <p class="mb-4 text-sm font-medium text-green-700">Nivel actualizado correctamente.</p>
@endif
@if (session('status') === 'nivel-deleted')
    <p class="mb-4 text-sm font-medium text-green-700">Nivel eliminado correctamente.</p>
@endif
@if (session('status') === 'subnivel-created')
    <p class="mb-4 text-sm font-medium text-green-700">Subnivel creado correctamente. Ya puedes agregar grados.</p>
@endif
@if (session('status') === 'subnivel-updated')
    <p class="mb-4 text-sm font-medium text-green-700">Subnivel actualizado correctamente.</p>
@endif
@if (session('status') === 'subnivel-deleted')
    <p class="mb-4 text-sm font-medium text-green-700">Subnivel eliminado correctamente.</p>
@endif
@if (session('status') === 'grado-created')
    <p class="mb-4 text-sm font-medium text-green-700">Grado creado correctamente.</p>
@endif
@if (session('status') === 'grado-updated')
    <p class="mb-4 text-sm font-medium text-green-700">Grado actualizado correctamente.</p>
@endif
@if (session('status') === 'grado-deleted')
    <p class="mb-4 text-sm font-medium text-green-700">Grado eliminado correctamente.</p>
@endif
@if (session('error'))
    <p class="mb-4 text-sm font-medium text-red-700">{{ session('error') }}</p>
@endif
