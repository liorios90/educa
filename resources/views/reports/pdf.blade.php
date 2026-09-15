<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $report->name }}</title>
    <style>
        @page { margin: 16mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #1e293b; }
        h1 { font-size: 16px; margin: 0 0 12px; }
        .sheet {
            position: relative;
            height: 230mm;
            border: 1px solid #cbd5e1;
            page-break-after: always;
        }
        .sheet:last-child { page-break-after: auto; }
        .label {
            position: absolute;
            max-width: 40%;
            font-size: 11px;
            font-weight: bold;
            color: #64748b;
        }
        .value {
            position: absolute;
            max-width: 50%;
            font-size: 13px;
        }
        .empty { padding: 24px; font-size: 13px; color: #64748b; }
    </style>
</head>
<body>
    <h1>{{ $report->name }}</h1>

    @forelse ($rows as $row)
        <div class="sheet">
            @foreach ($report->fields as $field)
                <div class="label" style="left: {{ (int) $field->label_x }}%; top: {{ (int) $field->label_y }}%;">
                    {{ $field->label }}
                </div>
                <div class="value" style="left: {{ (int) $field->value_x }}%; top: {{ (int) $field->value_y }}%;">
                    {{ $row[$field->column] ?? '—' }}
                </div>
            @endforeach
        </div>
    @empty
        <p class="empty">No hay datos para este reporte.</p>
    @endforelse
</body>
</html>
