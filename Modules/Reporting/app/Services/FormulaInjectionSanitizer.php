<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

/**
 * Prevents spreadsheet formula injection in exported Excel files.
 *
 * Any cell whose string value starts with one of the characters that
 * spreadsheet applications interpret as formula prefixes is prepended with
 * a single-quote character `'`, which causes the application to treat the
 * cell as plain text rather than a formula.
 *
 * Affected prefixes:  =  +  -  @  (tab)  (CR)
 *
 * Reference: OWASP — Formula Injection / CSV Injection
 * https://owasp.org/www-community/attacks/CSV_Injection
 */
final class FormulaInjectionSanitizer
{
    private const FORMULA_PREFIXES = ['=', '+', '-', '@', "\t", "\r"];

    /**
     * Sanitize a single scalar cell value.
     *
     * Non-string and non-numeric values (null, bool, int, float) are returned
     * unchanged — only string cells can carry injected formulas.
     */
    public function sanitizeCell(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        foreach (self::FORMULA_PREFIXES as $prefix) {
            if (str_starts_with($value, $prefix)) {
                return "'".$value;
            }
        }

        return $value;
    }

    /**
     * Sanitize every string value in a flat row array.
     *
     * @param  array<int|string, mixed>  $row
     * @return array<int|string, mixed>
     */
    public function sanitizeRow(array $row): array
    {
        return array_map($this->sanitizeCell(...), $row);
    }

    /**
     * Sanitize all rows in a collection, converting each object to an array.
     *
     * @param  iterable<object|array<mixed>>  $rows
     * @return list<array<mixed>>
     */
    public function sanitizeRows(iterable $rows): array
    {
        $result = [];

        foreach ($rows as $row) {
            $arr = is_array($row) ? $row : (array) $row;
            $result[] = $this->sanitizeRow($arr);
        }

        return $result;
    }
}
