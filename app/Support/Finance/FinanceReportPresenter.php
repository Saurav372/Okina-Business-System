<?php

namespace App\Support\Finance;

class FinanceReportPresenter
{
    /**
     * String-based float-free money formatting.
     *
     * Converts a minor unit integer or numeric string (e.g. "250000" or 250000)
     * into a display currency string ("₹2,500.00") without float division.
     */
    public static function formatMinor(string|int $minorAmount): string
    {
        $raw = (string) $minorAmount;
        $isNegative = str_starts_with($raw, '-');
        $cleanDigits = preg_replace('/[^\d]/', '', $raw) ?: '0';

        // Pad to at least 3 digits to separate major and 2 decimal places
        $padded = str_pad($cleanDigits, 3, '0', STR_PAD_LEFT);
        $majorPart = substr($padded, 0, -2);
        $centsPart = substr($padded, -2);

        // Standard western digit grouping for major units using integer arithmetic
        $formattedMajor = number_format((int) $majorPart, 0, '', ',');

        return ($isNegative ? '-' : '').'₹'.$formattedMajor.'.'.$centsPart;
    }

    /**
     * Present summary DTO for Blade / JSON consumption with formatted values attached.
     */
    public static function present(FinanceReportSummary $summary): array
    {
        $raw = $summary->toArray();

        $metrics = $raw['metrics'];
        $formattedMetrics = [];

        foreach ($metrics as $key => $value) {
            $formattedMetrics[$key] = (string) $value;
            if (str_ends_with($key, '_minor')) {
                $baseKey = substr($key, 0, -6);
                $formattedMetrics[$baseKey.'_formatted'] = self::formatMinor((string) $value);
            }
        }

        $formattedTrend = array_map(function (array $row) {
            $rowFormatted = $row;
            foreach ($row as $k => $v) {
                if (str_ends_with($k, '_minor')) {
                    $baseKey = substr($k, 0, -6);
                    $rowFormatted[$baseKey.'_formatted'] = self::formatMinor((string) $v);
                }
            }

            return $rowFormatted;
        }, $raw['monthly_trend']);

        $formattedCategories = array_map(function (array $cat) {
            $catFormatted = $cat;
            if (isset($cat['total_minor'])) {
                $catFormatted['total_formatted'] = self::formatMinor((string) $cat['total_minor']);
            }

            return $catFormatted;
        }, $raw['expense_categories']);

        return [
            'currency' => $summary->currency,
            'filters' => $raw['filters'],
            'metrics' => $formattedMetrics,
            'monthly_trend' => $formattedTrend,
            'expense_categories' => $formattedCategories,
        ];
    }
}
