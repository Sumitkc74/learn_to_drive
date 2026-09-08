<?php

namespace App\Support;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class QuestionSpreadsheetReader
{
    public static function read(string $path, string $extension): array
    {
        return $extension === 'xlsx' ? self::readXlsx($path) : self::readCsv($path);
    }

    private static function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) throw new RuntimeException('The CSV file could not be opened.');
        $rows = [];
        while (($values = fgetcsv($handle)) !== false) $rows[] = $values;
        fclose($handle);
        return $rows;
    }

    private static function readXlsx(string $path): array
    {
        if (!class_exists(ZipArchive::class)) throw new RuntimeException('Excel imports require the PHP ZIP extension.');
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) throw new RuntimeException('The Excel workbook could not be opened.');

        try {
            $sharedStrings = self::sharedStrings($zip);
            $sheetXml = self::safeEntry($zip, 'xl/worksheets/sheet1.xml');
            if ($sheetXml === null) throw new RuntimeException('The workbook does not contain a readable first sheet.');
            $sheet = simplexml_load_string($sheetXml, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
            if ($sheet === false) throw new RuntimeException('The first worksheet is invalid.');

            $rows = [];
            foreach ($sheet->sheetData->row as $row) {
                $values = [];
                foreach ($row->c as $cell) {
                    $column = self::columnIndex((string) $cell['r']);
                    $type = (string) $cell['t'];
                    $value = $type === 'inlineStr' ? (string) $cell->is->t : (string) $cell->v;
                    if ($type === 's') $value = $sharedStrings[(int) $value] ?? '';
                    $values[$column] = $value;
                }
                if ($values !== []) {
                    $width = max(array_keys($values)) + 1;
                    $rows[] = array_map('strval', array_replace(array_fill(0, $width, ''), $values));
                }
            }
            return $rows;
        } finally {
            $zip->close();
        }
    }

    private static function sharedStrings(ZipArchive $zip): array
    {
        $xml = self::safeEntry($zip, 'xl/sharedStrings.xml');
        if ($xml === null) return [];
        $document = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        if ($document === false) throw new RuntimeException('The workbook shared strings are invalid.');
        $strings = [];
        foreach ($document->si as $item) {
            if (isset($item->t)) $strings[] = (string) $item->t;
            else {
                $value = '';
                foreach ($item->r as $run) $value .= (string) $run->t;
                $strings[] = $value;
            }
        }
        return $strings;
    }

    private static function safeEntry(ZipArchive $zip, string $name): ?string
    {
        $index = $zip->locateName($name);
        if ($index === false) return null;
        $stat = $zip->statIndex($index);
        if (($stat['size'] ?? 0) > 10 * 1024 * 1024) throw new RuntimeException('The Excel worksheet is too large.');
        $contents = $zip->getFromIndex($index);
        return $contents === false ? null : $contents;
    }

    private static function columnIndex(string $reference): int
    {
        preg_match('/^[A-Z]+/i', $reference, $match);
        $index = 0;
        foreach (str_split(strtoupper($match[0] ?? 'A')) as $letter) $index = ($index * 26) + ord($letter) - 64;
        return max(0, $index - 1);
    }
}
