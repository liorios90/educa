@php
    $cellStyle = sprintf(
        'border: %dpx solid %s; padding: %dpx; text-align: left;',
        (int) $report->table_border_width,
        $report->table_border_color,
        (int) $report->table_cell_padding,
    );
    $headerStyle = $cellStyle.sprintf(' background-color: %s; font-weight: bold;', $report->table_header_background);
@endphp

<table style="border-collapse: collapse; width: 100%; font-size: {{ (int) $report->table_font_size }}px;">
    @if ($report->table_header)
        <thead>
            <tr>
                @foreach ($report->fields as $field)
                    <th style="{{ $headerStyle }}">{{ $field->label }}</th>
                @endforeach
            </tr>
        </thead>
    @endif
    <tbody>
        @forelse ($rows as $row)
            <tr @if ($report->table_striped && $loop->odd) style="background-color: #f8fafc;" @endif>
                @foreach ($report->fields as $field)
                    <td style="{{ $cellStyle }}">{{ $row[$field->column] ?? '—' }}</td>
                @endforeach
            </tr>
        @empty
            <tr>
                <td style="{{ $cellStyle }}" colspan="{{ max(count($report->fields), 1) }}">
                    No hay datos para este reporte.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
