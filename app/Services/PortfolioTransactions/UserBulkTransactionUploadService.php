<?php

namespace App\Services\PortfolioTransactions;

use App\Models\Etf;
use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class UserBulkTransactionUploadService
{
    private const HEADER_ALIASES = [

        'symbol' => [

            'symbol',

            'stock',

            'ticker',

            'etf',

            'etf_symbol',

        ],

        'transaction_type' => [

            'transaction_type',

            'type',

            'transaction',

            'action',

            'buy_sell',

        ],

        'shares' => [

            'shares',

            'share',

            'share_count',

            'count',

            'quantity',

            'qty',

        ],

        'price_per_share' => [

            'price_per_share',

            'price',

            'cost',

            'cost_per_share',

            'share_price',

        ],

        'transaction_date' => [

            'transaction_date',

            'date',

            'trade_date',

            'purchase_date',

            'sold_date',

        ],

    ];

    public function import(
        int $userId,
        int $portfolioId,
        UploadedFile $file
    ): array {
        Portfolio::where('user_id', $userId)
            ->where('id', $portfolioId)
            ->firstOrFail();

        $rows = $this->parseCsv($file);

        $summary = [
            'total_rows' => count($rows),
            'imported_rows' => 0,
            'duplicate_rows' => 0,
            'failed_rows' => 0,
            'errors' => [],
        ];

        foreach ($rows as $index => $row) {

            $rowNumber = $index + 2;

            try {

                $normalized = $this->normalizeRow($row);

                $etf = Etf::where('symbol', $normalized['symbol'])
                    ->first();

                if (! $etf) {
                    throw new \InvalidArgumentException("ETF symbol [{$normalized['symbol']}] was not found.");
                }

                $transactionTypeId = $this->resolveTransactionTypeId(
                    $normalized['transaction_type']
                );

                if (! $transactionTypeId) {
                    throw new \InvalidArgumentException("Transaction type [{$normalized['transaction_type']}] is not supported.");
                }

                $isDuplicate = PortfolioTransaction::where('portfolio_id', $portfolioId)
                    ->where('etf_id', $etf->id)
                    ->where('transaction_type_id', $transactionTypeId)
                    ->where('shares', $normalized['shares'])
                    ->where('price_per_share', $normalized['price_per_share'])
                    ->whereDate('transaction_date', $normalized['transaction_date'])
                    ->exists();

                if ($isDuplicate) {
                    $summary['duplicate_rows']++;

                    continue;
                }

                PortfolioTransaction::create([
                    'portfolio_id' => $portfolioId,
                    'etf_id' => $etf->id,
                    'transaction_type_id' => $transactionTypeId,
                    'shares' => $normalized['shares'],
                    'price_per_share' => $normalized['price_per_share'],
                    'transaction_date' => $normalized['transaction_date'],
                ]);

                $summary['imported_rows']++;
            } catch (\Exception $e) {

                $summary['failed_rows']++;

                $summary['errors'][] = [
                    'row' => $rowNumber,
                    'message' => $e->getMessage(),
                    'data' => $row,
                ];
            }
        }

        return $summary;
    }

    private function parseCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if (! $handle) {
            throw new \RuntimeException('Unable to open uploaded CSV file.');
        }

        $headers = fgetcsv($handle);

        if (! $headers) {
            fclose($handle);

            throw new \RuntimeException('CSV file is empty.');
        }

        $headers = collect($headers)

            ->map(fn ($header) => $this->resolveHeaderAlias($header))

            ->toArray();

        $headers = collect($headers)
            ->map(fn ($header) => Str::snake(strtolower(trim($header))))
            ->toArray();

        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {

            if ($this->isEmptyRow($data)) {
                continue;
            }

            $rows[] = array_combine($headers, $data);
        }

        fclose($handle);

        return $rows;
    }

    private function normalizeRow(array $row): array
    {
        foreach (
            [
                'symbol',
                'transaction_type',
                'shares',
                'price_per_share',
                'transaction_date',
            ] as $requiredColumn
        ) {

            if (! array_key_exists($requiredColumn, $row)) {
                throw new \InvalidArgumentException("Missing required column [{$requiredColumn}].");
            }
        }

        $shares = $this->normalizeDecimal($row['shares']);

        $pricePerShare = $this->normalizeDecimal($row['price_per_share']);

        if ($shares <= 0) {
            throw new \InvalidArgumentException('Shares must be greater than zero.');
        }

        if ($pricePerShare < 0) {
            throw new \InvalidArgumentException('Price per share cannot be negative.');
        }

        return [
            'symbol' => strtoupper(trim($row['symbol'])),

            'transaction_type' => strtolower(trim($row['transaction_type'])),

            'shares' => number_format($shares, 4, '.', ''),

            'price_per_share' => number_format($pricePerShare, 4, '.', ''),

            'transaction_date' => Carbon::parse($row['transaction_date'])->format('Y-m-d'),
        ];
    }

    private function resolveTransactionTypeId(string $transactionType): ?int
    {
        return config("import_transaction_aliases.aliases.{$transactionType}");
    }

    private function isEmptyRow(array $row): bool
    {
        return collect($row)
            ->filter(fn ($value) => trim((string) $value) !== '')
            ->isEmpty();
    }

    private function resolveHeaderAlias(string $header): string
    {
        $normalized = Str::snake(strtolower(trim($header)));

        foreach (self::HEADER_ALIASES as $canonical => $aliases) {
            if (in_array($normalized, $aliases, true)) {
                return $canonical;
            }
        }

        return $normalized;
    }

    private function normalizeDecimal($value): float
    {
        return (float) str_replace(
            ['$', ',', ' '],
            '',
            trim((string) $value)
        );
    }
}
