<?php

namespace Tests\Feature\Operating;

use App\Services\Spreadsheet;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

class CrmSpreadsheetTest extends TestCase
{
    private function temp(string $content, string $extension): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sheet').'.'.$extension;
        file_put_contents($path, $content);

        return $path;
    }

    public function test_written_workbooks_read_back_with_arabic_text_and_numbers(): void
    {
        $sheet = app(Spreadsheet::class);
        $binary = $sheet->xlsx([['الاسم', 'Value'], ['عمر حسن', 1500], ['=cmd()', null]], 'Leads', true);

        $rows = $sheet->read($this->temp($binary, 'xlsx'), 'xlsx');

        $this->assertSame([['الاسم', 'Value'], ['عمر حسن', '1500'], ['=cmd()']], $rows);
    }

    public function test_csv_with_bom_and_semicolons_is_parsed_and_blank_rows_dropped(): void
    {
        $rows = app(Spreadsheet::class)->read($this->temp("\xEF\xBB\xBFname;phone\n\"Omar; Jr\";0100\n;\nSara;0111\n", 'csv'), 'csv');

        $this->assertSame([['name', 'phone'], ['Omar; Jr', '0100'], ['Sara', '0111']], $rows);
    }

    public function test_shared_strings_from_excel_files_are_resolved(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'excel').'.xlsx';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $zip->addFromString('xl/sharedStrings.xml', '<?xml version="1.0"?><sst xmlns="'.$ns.'"><si><t>Client Name</t></si><si><r><t>نور </t></r><r><t>الدين</t></r></si></sst>');
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0"?><worksheet xmlns="'.$ns.'"><sheetData><row r="1"><c r="A1" t="s"><v>0</v></c><c r="C1"><v>7</v></c></row><row r="2"><c r="A2" t="s"><v>1</v></c></row></sheetData></worksheet>');
        $zip->close();

        $this->assertSame([['Client Name', '', '7'], ['نور الدين']], app(Spreadsheet::class)->read($path, 'xlsx'));
    }

    public function test_non_utf8_csv_is_refused_with_a_clear_reason(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('encoding');

        app(Spreadsheet::class)->read($this->temp("name\n\xC7\xE1\xD3\xE3\n", 'csv'), 'csv');
    }
}
