<?php

namespace App\Services;

use InvalidArgumentException;
use RuntimeException;

class SpreadsheetReader
{
    private const SPREADSHEET_NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    /**
     * @return list<array{num_fila: int, values: array<string, string>}>
     */
    public function rows(string $path, string $extension): array
    {
        $extension = strtolower($extension);

        $table = match ($extension) {
            'csv' => $this->csvTable($path),
            'xlsx' => $this->xlsxTable($path),
            default => throw new InvalidArgumentException('El archivo debe ser xlsx o csv.'),
        };

        if ($table === []) {
            return [];
        }

        $headers = array_map(fn (string $header): string => $this->normalizeHeader($header), $table[0]);
        $rows = [];

        foreach (array_slice($table, 1, preserve_keys: true) as $index => $cells) {
            $values = [];

            foreach ($headers as $column => $header) {
                if ($header === '') {
                    continue;
                }

                $values[$header] = trim((string) ($cells[$column] ?? ''));
            }

            if ($this->rowIsEmpty($values)) {
                continue;
            }

            $rows[] = [
                'num_fila' => $index + 1,
                'values' => $values,
            ];
        }

        return $rows;
    }

    /**
     * @return list<list<string>>
     */
    private function csvTable(string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException('No se pudo leer el archivo.');
        }

        try {
            $firstLine = fgets($handle);

            if ($firstLine === false) {
                return [];
            }

            $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine) ?? $firstLine;
            $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
            rewind($handle);

            $table = [];

            while (($cells = fgetcsv($handle, 0, $delimiter)) !== false) {
                if ($table === [] && isset($cells[0])) {
                    $cells[0] = (string) preg_replace('/^\xEF\xBB\xBF/', '', (string) $cells[0]);
                }

                $table[] = array_map(fn (mixed $cell): string => trim((string) $cell), $cells);
            }

            return $table;
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return list<list<string>>
     */
    private function xlsxTable(string $path): array
    {
        $entries = $this->zipEntries($path);
        $sharedStrings = $this->sharedStrings($this->zipFile($entries, 'xl/sharedStrings.xml') ?? '');
        $sheetXml = $this->zipFile($entries, 'xl/worksheets/sheet1.xml');

        if (! is_string($sheetXml) || $sheetXml === '') {
            throw new RuntimeException('El archivo Excel no contiene una hoja de datos.');
        }

        $sheet = simplexml_load_string($sheetXml);

        if ($sheet === false) {
            throw new RuntimeException('El archivo Excel no es válido.');
        }

        $sheet->registerXPathNamespace('m', self::SPREADSHEET_NS);
        $rowNodes = $sheet->xpath('//m:sheetData/m:row') ?: [];
        $table = [];

        foreach ($rowNodes as $rowNode) {
            $rowIndex = max(1, (int) $rowNode['r']);
            $cells = [];

            foreach ($rowNode->children(self::SPREADSHEET_NS) as $cell) {
                if ($cell->getName() !== 'c') {
                    continue;
                }

                $reference = (string) ($cell->attributes()['r'] ?? '');
                $column = $this->columnIndex($reference);
                $cells[$column] = $this->cellValue($cell, $sharedStrings);
            }

            if ($cells === []) {
                continue;
            }

            $width = max(array_keys($cells)) + 1;
            $ordered = [];

            for ($column = 0; $column < $width; $column++) {
                $ordered[$column] = $cells[$column] ?? '';
            }

            $table[$rowIndex - 1] = $ordered;
        }

        if ($table === []) {
            return [];
        }

        ksort($table);

        $normalized = [];
        $lastIndex = max(array_keys($table));

        for ($index = 0; $index <= $lastIndex; $index++) {
            $normalized[$index] = $table[$index] ?? [''];
        }

        return $normalized;
    }

    /**
     * @return array<string, string>
     */
    private function zipEntries(string $path): array
    {
        $binary = file_get_contents($path);

        if ($binary === false || $binary === '') {
            throw new RuntimeException('No se pudo abrir el archivo Excel.');
        }

        $eocd = strrpos($binary, "PK\x05\x06");

        if ($eocd === false) {
            throw new RuntimeException('No se pudo abrir el archivo Excel.');
        }

        $meta = unpack('vdisk/vstart/vthis/vtotal/Vsize/Voffset/vcomment', substr($binary, $eocd + 4, 18));

        if ($meta === false || $meta['total'] === 0 || $meta['total'] === 0xFFFF || $meta['offset'] === 0xFFFFFFFF) {
            throw new RuntimeException('No se pudo abrir el archivo Excel.');
        }

        $entries = [];
        $cursor = $meta['offset'];

        for ($index = 0; $index < $meta['total']; $index++) {
            if (substr($binary, $cursor, 4) !== "PK\x01\x02") {
                throw new RuntimeException('No se pudo abrir el archivo Excel.');
            }

            $header = unpack(
                'vmade/vneed/vflags/vmethod/vtime/vdate/Vcrc/Vcomp/Vuncomp/vnameLen/vextraLen/vcommentLen/vdisk/vinternal/Vexternal/Vlocal',
                substr($binary, $cursor + 4, 42),
            );

            if ($header === false) {
                throw new RuntimeException('No se pudo abrir el archivo Excel.');
            }

            $name = str_replace('\\', '/', substr($binary, $cursor + 46, $header['nameLen']));
            $cursor += 46 + $header['nameLen'] + $header['extraLen'] + $header['commentLen'];

            if ($name === '' || str_ends_with($name, '/')) {
                continue;
            }

            $entries[$name] = $this->inflateZipEntry($binary, $header);
        }

        return $entries;
    }

    /**
     * @param  array{flags: int, method: int, comp: int, local: int, uncomp: int}  $header
     */
    private function inflateZipEntry(string $binary, array $header): string
    {
        if (($header['flags'] & 1) === 1 || substr($binary, $header['local'], 4) !== "PK\x03\x04") {
            throw new RuntimeException('No se pudo abrir el archivo Excel.');
        }

        $local = unpack(
            'vneed/vflags/vmethod/vtime/vdate/Vcrc/Vcomp/Vuncomp/vnameLen/vextraLen',
            substr($binary, $header['local'] + 4, 26),
        );

        if ($local === false) {
            throw new RuntimeException('No se pudo abrir el archivo Excel.');
        }

        $payload = substr($binary, $header['local'] + 30 + $local['nameLen'] + $local['extraLen'], $header['comp']);

        return match ($header['method']) {
            0 => $payload,
            8 => $this->inflateDeflate($payload),
            default => throw new RuntimeException('No se pudo abrir el archivo Excel.'),
        };
    }

    private function inflateDeflate(string $payload): string
    {
        $inflated = @gzinflate($payload);

        if (! is_string($inflated)) {
            throw new RuntimeException('No se pudo abrir el archivo Excel.');
        }

        return $inflated;
    }

    /**
     * @param  array<string, string>  $entries
     */
    private function zipFile(array $entries, string $name): ?string
    {
        if (isset($entries[$name])) {
            return $entries[$name];
        }

        foreach ($entries as $entry => $contents) {
            if (strcasecmp($entry, $name) === 0) {
                return $contents;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function sharedStrings(string $xml): array
    {
        if ($xml === '') {
            return [];
        }

        $document = simplexml_load_string($xml);

        if ($document === false) {
            return [];
        }

        $document->registerXPathNamespace('m', self::SPREADSHEET_NS);
        $strings = [];

        foreach ($document->xpath('//m:si') ?: [] as $item) {
            $strings[] = trim($this->sharedStringValue($item));
        }

        return $strings;
    }

    private function sharedStringValue(\SimpleXMLElement $item): string
    {
        $text = '';

        foreach ($item->children(self::SPREADSHEET_NS) as $child) {
            $text .= match ($child->getName()) {
                't' => (string) $child,
                'r' => (string) $child->children(self::SPREADSHEET_NS)->t,
                default => '',
            };
        }

        return $text;
    }

    /**
     * @param  list<string>  $sharedStrings
     */
    private function cellValue(\SimpleXMLElement $cell, array $sharedStrings): string
    {
        $type = (string) ($cell->attributes()['t'] ?? '');
        $children = $cell->children(self::SPREADSHEET_NS);

        if ($type === 'inlineStr') {
            return trim((string) $children->is->children(self::SPREADSHEET_NS)->t);
        }

        $value = trim((string) $children->v);

        if ($type === 's') {
            return $sharedStrings[(int) $value] ?? '';
        }

        return $value;
    }

    private function columnIndex(string $reference): int
    {
        preg_match('/^[A-Z]+/', strtoupper($reference), $matches);
        $letters = $matches[0] ?? 'A';
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index - 1;
    }

    private function normalizeHeader(string $header): string
    {
        $normalized = strtolower(trim($header));
        $normalized = str_replace([' ', '-'], '_', $normalized);

        return trim($normalized, '_');
    }

    /**
     * @param  array<string, string>  $values
     */
    private function rowIsEmpty(array $values): bool
    {
        foreach ($values as $value) {
            if ($value !== '') {
                return false;
            }
        }

        return true;
    }
}
