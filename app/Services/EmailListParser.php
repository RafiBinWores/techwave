<?php

namespace App\Services;

use ZipArchive;

class EmailListParser
{
    /**
     * Extract email addresses from an uploaded CSV, TXT, or XLSX file.
     *
     * @return list<string>
     */
    public function fromFile(string $path, string $extension): array
    {
        $content = strtolower($extension) === 'xlsx'
            ? $this->xlsxText($path)
            : (string) @file_get_contents($path);

        return $this->fromText($content);
    }

    /**
     * Extract email addresses from arbitrary text (CSV rows, pasted lists, sheet cells).
     *
     * @return list<string>
     */
    public function fromText(string $content): array
    {
        preg_match_all('/[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}/', $content, $matches);

        return array_values(array_unique($matches[0]));
    }

    /**
     * Flatten an XLSX workbook to plain text (shared strings + worksheet cells).
     */
    private function xlsxText(string $path): string
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            return '';
        }

        $xml = '';

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);

            if ($name === 'xl/sharedStrings.xml' || str_starts_with($name, 'xl/worksheets/')) {
                $xml .= $zip->getFromIndex($i)."\n";
            }
        }

        $zip->close();

        preg_match_all('/<t[^>]*>(.*?)<\/t>/s', $xml, $matches);

        return implode("\n", array_map(
            static fn (string $value): string => html_entity_decode($value, ENT_QUOTES | ENT_XML1, 'UTF-8'),
            $matches[1],
        ));
    }
}
