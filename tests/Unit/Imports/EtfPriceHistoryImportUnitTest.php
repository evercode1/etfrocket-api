<?php

namespace Tests\Unit\Imports;

use App\Imports\EtfPriceHistoryImport;
use RuntimeException;
use Tests\TestCase;

class EtfPriceHistoryImportUnitTest extends TestCase
{
    public function test_it_can_parse_etf_price_history_text_file(): void
    {
        $filePath = $this->makeTextFile([
            'Date',
            'Open',
            'High',
            'Low',
            'Close',
            'Adj Close',
            'Volume',

            'May 15, 2026',
            '29.34',
            '29.53',
            '28.47',
            '28.58',
            '28.58',
            '190,900',

            'May 14, 2026',
            '29.64',
            '30.22',
            '29.33',
            '30.20',
            '30.20',
            '381,300',
        ]);

        $parsed = (new EtfPriceHistoryImport)->parse($filePath);

        $this->assertCount(2, $parsed['prices']);
        $this->assertCount(0, $parsed['dividends']);

        $this->assertSame([
            'price_date' => '2026-05-15',
            'open_price' => '29.3400',
            'high_price' => '29.5300',
            'low_price' => '28.4700',
            'close_price' => '28.5800',
            'volume' => 190900,
        ], $parsed['prices'][0]);

        $this->assertSame([
            'price_date' => '2026-05-14',
            'open_price' => '29.6400',
            'high_price' => '30.2200',
            'low_price' => '29.3300',
            'close_price' => '30.2000',
            'volume' => 381300,
        ], $parsed['prices'][1]);

        unlink($filePath);
    }

    public function test_it_can_parse_dividend_rows_from_text_file(): void
    {
        $filePath = $this->makeTextFile([
            'Date',
            'Open',
            'High',
            'Low',
            'Close',
            'Adj Close',
            'Volume',

            'May 12, 2026',
            '0.239 Dividend',

            'May 12, 2026',
            '28.48',
            '29.02',
            '28.08',
            '28.82',
            '28.82',
            '211,500',

            'May 5, 2026',
            '0.218 Dividend',

            'May 5, 2026',
            '26.40',
            '26.43',
            '25.91',
            '25.98',
            '25.76',
            '75,400',
        ]);

        $parsed = (new EtfPriceHistoryImport)->parse($filePath);

        $this->assertCount(2, $parsed['prices']);
        $this->assertCount(2, $parsed['dividends']);

        $this->assertSame([
            'ex_dividend_date' => '2026-05-12',
            'dividend_amount' => '0.2390',
        ], $parsed['dividends'][0]);

        $this->assertSame([
            'ex_dividend_date' => '2026-05-05',
            'dividend_amount' => '0.2180',
        ], $parsed['dividends'][1]);

        $this->assertSame('2026-05-12', $parsed['prices'][0]['price_date']);
        $this->assertSame('28.8200', $parsed['prices'][0]['close_price']);

        unlink($filePath);
    }

    public function test_it_skips_empty_rows(): void
    {
        $filePath = $this->makeTextFile([
            'Date',
            'Open',
            'High',
            'Low',
            'Close',
            'Adj Close',
            'Volume',

            'May 15, 2026',
            '29.34',
            '29.53',
            '28.47',
            '28.58',
            '28.58',
            '190,900',

            '',
            '   ',

            'May 14, 2026',
            '29.64',
            '30.22',
            '29.33',
            '30.20',
            '30.20',
            '381,300',
        ]);

        $parsed = (new EtfPriceHistoryImport)->parse($filePath);

        $this->assertCount(2, $parsed['prices']);

        unlink($filePath);
    }

    public function test_it_handles_non_breaking_spaces_in_dividend_rows(): void
    {
        $filePath = $this->makeTextFile([
            'Date',
            'Open',
            'High',
            'Low',
            'Close',
            'Adj Close',
            'Volume',

            'May 12, 2026',
            "0.239\u{00A0}Dividend\u{00A0}",

            'May 12, 2026',
            '28.48',
            '29.02',
            '28.08',
            '28.82',
            '28.82',
            '211,500',
        ]);

        $parsed = (new EtfPriceHistoryImport)->parse($filePath);

        $this->assertCount(1, $parsed['dividends']);
        $this->assertSame('0.2390', $parsed['dividends'][0]['dividend_amount']);

        unlink($filePath);
    }

    public function test_it_throws_exception_when_file_does_not_exist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Import file not found at path [/bad/path.txt].');

        (new EtfPriceHistoryImport)->parse('/bad/path.txt');
    }

    public function test_it_throws_exception_when_file_is_empty(): void
    {
        $filePath = tempnam(sys_get_temp_dir(), 'empty-etf-price-history-import-');

        file_put_contents($filePath, '');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Import file is empty.');

        try {
            (new EtfPriceHistoryImport)->parse($filePath);
        } finally {
            unlink($filePath);
        }
    }

    private function makeTextFile(array $lines): string
    {
        $filePath = tempnam(sys_get_temp_dir(), 'etf-price-history-import-');

        file_put_contents(
            $filePath,
            collect($lines)->implode(PHP_EOL)
        );

        return $filePath;
    }
}
