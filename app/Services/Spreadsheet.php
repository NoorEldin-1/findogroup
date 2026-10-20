<?php

namespace App\Services;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class Spreadsheet
{
    private const MAIN_NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    private const REL_NS = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    /**
     * @return list<list<string>>
     */
    public function read(string $path, string $extension): array
    {
        $rows = strtolower($extension) === 'xlsx' ? $this->readXlsx($path) : $this->readCsv($path);

        return array_values(array_filter($rows, fn (array $row): bool => implode('', $row) !== ''));
    }

    /**
     * Text cells are written as inline strings, so a value such as "=cmd()" stays text and never becomes a formula.
     *
     * @param  list<list<string|int|float|null>>  $rows
     */
    public function xlsx(array $rows, string $sheetName = 'Sheet1', bool $rightToLeft = false): string
    {
        $sheet = '';
        foreach (array_values($rows) as $r => $row) {
            $cells = '';
            foreach (array_values($row) as $c => $value) {
                $ref = $this->column($c).($r + 1);
                if (is_int($value) || is_float($value)) {
                    $cells .= '<c r="'.$ref.'"><v>'.$value.'</v></c>';
                } elseif ($value !== null && $value !== '') {
                    $cells .= '<c r="'.$ref.'" t="inlineStr"><is><t xml:space="preserve">'.$this->escape((string) $value).'</t></is></c>';
                }
            }
            $sheet .= '<row r="'.($r + 1).'">'.$cells.'</row>';
        }
        $head = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('unwritable');
        }
        $zip->addFromString('[Content_Types].xml', $head.'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('_rels/.rels', $head.'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', $head.'<workbook xmlns="'.self::MAIN_NS.'" xmlns:r="'.self::REL_NS.'"><sheets><sheet name="'.$this->escape(mb_substr($sheetName, 0, 31)).'" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', $head.'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $zip->addFromString('xl/worksheets/sheet1.xml', $head.'<worksheet xmlns="'.self::MAIN_NS.'"><sheetViews><sheetView workbookViewId="0"'.($rightToLeft ? ' rightToLeft="1"' : '').'/></sheetViews><sheetData>'.$sheet.'</sheetData></worksheet>');
        $zip->close();
        $binary = (string) file_get_contents($path);
        unlink($path);

        return $binary;
    }

    /**
     * @return list<list<string>>
     */
    private function readCsv(string $path): array
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', (string) file_get_contents($path));
        if (! mb_check_encoding($content, 'UTF-8')) {
            throw new RuntimeException('encoding');
        }
        $firstLine = strtok($content, "\n") ?: '';
        $delimiter = collect([',', ';', "\t"])->sortByDesc(fn (string $candidate): int => substr_count($firstLine, $candidate))->first();
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $content);
        rewind($handle);
        $rows = [];
        while (($row = fgetcsv($handle, null, $delimiter, '"', '')) !== false) {
            $rows[] = array_map(fn (mixed $cell): string => trim((string) $cell), $row);
        }
        fclose($handle);

        return $rows;
    }

    /**
     * @return list<list<string>>
     */
    private function readXlsx(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('unreadable');
        }
        $shared = [];
        if (($strings = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            foreach ($this->xml($strings)->si as $item) {
                $shared[] = isset($item->t) ? (string) $item->t : implode('', array_map(fn (SimpleXMLElement $run): string => (string) $run->t, iterator_to_array($item->r, false)));
            }
        }
        $sheetXml = $zip->getFromName($this->firstSheet($zip));
        $zip->close();
        if ($sheetXml === false) {
            throw new RuntimeException('unreadable');
        }
        $rows = [];
        foreach ($this->xml($sheetXml)->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $cell) {
                $ref = preg_replace('/\d+/', '', (string) $cell['r']);
                $index = $ref !== '' ? $this->columnIndex($ref) : count($cells);
                $cells[$index] = trim(match ((string) $cell['t']) {
                    's' => $shared[(int) $cell->v] ?? '',
                    'inlineStr' => (string) $cell->is->t,
                    'b' => (string) $cell->v === '1' ? 'TRUE' : 'FALSE',
                    default => (string) $cell->v,
                });
            }
            $rows[] = $cells ? array_map(fn (int $i): string => $cells[$i] ?? '', range(0, max(array_keys($cells)))) : [];
        }

        return $rows;
    }

    private function firstSheet(ZipArchive $zip): string
    {
        $workbook = $zip->getFromName('xl/workbook.xml');
        $relations = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($workbook !== false && $relations !== false) {
            $sheet = $this->xml($workbook)->sheets->sheet[0] ?? null;
            $id = $sheet ? (string) $sheet->attributes(self::REL_NS)['id'] : '';
            foreach ($this->xml($relations)->Relationship as $relation) {
                if ((string) $relation['Id'] === $id) {
                    $target = ltrim((string) $relation['Target'], '/');

                    return str_starts_with($target, 'xl/') ? $target : 'xl/'.$target;
                }
            }
        }

        return 'xl/worksheets/sheet1.xml';
    }

    private function xml(string $content): SimpleXMLElement
    {
        $xml = simplexml_load_string($content, SimpleXMLElement::class, LIBXML_NONET);
        if ($xml === false) {
            throw new RuntimeException('unreadable');
        }

        return $xml;
    }

    private function column(int $index): string
    {
        $name = '';
        for ($index++; $index > 0; $index = intdiv($index - 1, 26)) {
            $name = chr(65 + ($index - 1) % 26).$name;
        }

        return $name;
    }

    private function columnIndex(string $letters): int
    {
        $index = 0;
        foreach (str_split(strtoupper($letters)) as $letter) {
            $index = $index * 26 + ord($letter) - 64;
        }

        return $index - 1;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars(preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $value) ?? '', ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
