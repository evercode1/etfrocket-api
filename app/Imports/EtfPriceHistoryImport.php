<?php

namespace App\Imports;

use Carbon\Carbon;
use RuntimeException;

class EtfPriceHistoryImport
{
    public function parse(string $filePath): array
    {
        if (! file_exists($filePath)) {
            throw new RuntimeException("Import file not found at path [{$filePath}].");
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (! $lines) {
            throw new RuntimeException('Import file is empty.');
        }

        $lines = collect($lines)
            ->map(fn ($line) => trim(str_replace("\u{00A0}", ' ', $line)))
            ->filter(fn ($line) => $line !== '')
            ->values()
            ->toArray();

        $prices = [];
        $dividends = [];

        $index = 0;

        while ($index < count($lines)) {
            $line = $lines[$index];

            if ($this->isHeaderLine($line)) {
                $index++;

                continue;
            }

            if (! $this->isDateLine($line)) {
                $index++;

                continue;
            }

            $date = Carbon::parse($line)->format('Y-m-d');

            $nextLine = $lines[$index + 1] ?? null;

            if ($nextLine && str_contains(strtolower($nextLine), 'dividend')) {
                $dividends[] = [
                    'ex_dividend_date' => $date,
                    'dividend_amount' => $this->normalizeDecimal(
                        str_replace('Dividend', '', $nextLine)
                    ),
                ];

                $index += 2;

                continue;
            }

            if (! $this->hasValidPriceRow($lines, $index)) {
                $index++;

                continue;
            }

            $prices[] = [
                'price_date' => $date,
                'open_price' => $this->normalizeDecimal($lines[$index + 1]),
                'high_price' => $this->normalizeDecimal($lines[$index + 2]),
                'low_price' => $this->normalizeDecimal($lines[$index + 3]),
                'close_price' => $this->normalizeDecimal($lines[$index + 4]),
                'volume' => $this->normalizeInteger($lines[$index + 6]),
            ];

            $index += 7;
        }

        return [
            'prices' => $prices,
            'dividends' => $dividends,
        ];
    }

    private function isHeaderLine(string $line): bool
    {
        return in_array(strtolower($line), [
            'date',
            'open',
            'high',
            'low',
            'close',
            'adj close',
            'volume',
        ], true);
    }

    private function isDateLine(string $line): bool
    {
        return preg_match(
            '/^[A-Z][a-z]{2} \d{1,2}, \d{4}$/',
            trim($line)
        ) === 1;
    }

    private function hasValidPriceRow(array $lines, int $index): bool
    {
        foreach ([1, 2, 3, 4, 5, 6] as $offset) {
            if (! isset($lines[$index + $offset])) {
                return false;
            }

            if (! is_numeric(str_replace(',', '', trim($lines[$index + $offset])))) {
                return false;
            }
        }

        return true;
    }

    private function normalizeDecimal(mixed $value): string
    {
        $value = trim((string) $value);

        $value = str_replace([
            ',',
            'Dividend',
            "\u{00A0}",
        ], [
            '',
            '',
            ' ',
        ], $value);

        return number_format((float) trim($value), 4, '.', '');
    }

    private function normalizeInteger(mixed $value): int
    {
        return (int) str_replace(',', '', trim((string) $value));
    }
}
