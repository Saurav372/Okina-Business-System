<?php

namespace App\Support\GoogleSheets;

use Google\Client;
use Google\Service\Sheets;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Throwable;

class GoogleSheetsClient
{
    protected ?Client $client = null;

    /**
     * Get or build the Google Client.
     */
    public function getClient(): Client
    {
        if ($this->client !== null) {
            return $this->client;
        }

        $config = Config::get('sheets');

        $clientEmail = $config['credentials']['client_email'] ?? null;
        $privateKey = $config['credentials']['private_key'] ?? null;
        $projectId = $config['credentials']['project_id'] ?? null;

        if (! $clientEmail || ! $privateKey || ! $projectId) {
            throw new \RuntimeException('Google Sheets configuration is incomplete.');
        }

        // Normalize escaped newlines in private key
        $privateKeyNormalized = str_replace('\n', "\n", $privateKey);

        $client = new Client;
        $client->setAuthConfig([
            'type' => 'service_account',
            'project_id' => $projectId,
            'private_key' => $privateKeyNormalized,
            'client_email' => $clientEmail,
            'client_id' => $config['credentials']['client_id'] ?? 'dummy-client-id',
            'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
            'token_uri' => 'https://oauth2.googleapis.com/token',
            'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
            'client_x509_cert_url' => 'https://www.googleapis.com/robot/v1/metadata/x509/'.urlencode($clientEmail),
        ]);

        // Request full read/write access to spreadsheet contents
        $client->setScopes([Sheets::SPREADSHEETS]);

        $this->client = $client;

        return $this->client;
    }

    /**
     * Get the Sheets service instance.
     */
    public function getSheetsService(): Sheets
    {
        return new Sheets($this->getClient());
    }

    /**
     * Synchronize a row payload by seeking an existing unique identifier match first.
     */
    public function syncRow(string $sheetName, array $columnKeys, array $rowValues, string $uniqueKey, string $uniqueValue): void
    {
        if ($uniqueValue === '') {
            $this->appendRow($sheetName, $rowValues);

            return;
        }

        // Determine column letter for lookup
        $colIndex = array_search($uniqueKey, $columnKeys, true);
        $columnLetter = 'A';
        if ($colIndex !== false) {
            $columnLetter = $this->getColumnLetter((int) $colIndex);
        }

        $rowIndex = $this->findRowByKey($sheetName, $uniqueValue, $columnLetter);

        if ($rowIndex !== null) {
            $this->updateRow($sheetName, $rowIndex, $rowValues);
        } else {
            $this->appendRow($sheetName, $rowValues);
        }
    }

    /**
     * Convert 0-based column index to Google Sheets column letter (e.g. 0 -> A, 25 -> Z, 26 -> AA).
     */
    public function getColumnLetter(int $colIndex): string
    {
        $letter = '';
        while ($colIndex >= 0) {
            $letter = chr(($colIndex % 26) + 65).$letter;
            $colIndex = intval($colIndex / 26) - 1;
        }

        return $letter;
    }

    /**
     * Find a matching row index (1-based index) by searching a single column range.
     */
    public function findRowByKey(string $sheetName, string $uniqueValue, string $columnLetter = 'A'): ?int
    {
        $spreadsheetId = Config::get('sheets.spreadsheet_id');

        if (! $spreadsheetId) {
            throw new \RuntimeException('Spreadsheet ID is not configured.');
        }

        $service = $this->getSheetsService();
        $range = "{$sheetName}!{$columnLetter}:{$columnLetter}";

        try {
            $response = $service->spreadsheets_values->get($spreadsheetId, $range);
            $values = $response->getValues();

            if (! is_array($values)) {
                return null;
            }

            foreach ($values as $index => $row) {
                $cellValue = $row[0] ?? null;
                if ($cellValue !== null && (string) $cellValue === $uniqueValue) {
                    return $index + 1; // 1-based index
                }
            }
        } catch (Throwable $e) {
            Log::warning("Failed seeking row by key in sheet [{$sheetName}]: ".$e->getMessage());
        }

        return null;
    }

    /**
     * Update an entire row range with the provided values.
     */
    public function updateRow(string $sheetName, int $rowIndex, array $rowValues): void
    {
        $spreadsheetId = Config::get('sheets.spreadsheet_id');

        if (! $spreadsheetId) {
            throw new \RuntimeException('Spreadsheet ID is not configured.');
        }

        $service = $this->getSheetsService();
        // Target range A{rowIndex} onwards
        $range = "{$sheetName}!A{$rowIndex}";

        $body = new Sheets\ValueRange([
            'values' => [$rowValues],
        ]);

        $params = [
            'valueInputOption' => 'USER_ENTERED',
        ];

        $service->spreadsheets_values->update($spreadsheetId, $range, $body, $params);
    }

    /**
     * Append a row to the end of the sheet.
     */
    public function appendRow(string $sheetName, array $rowValues): void
    {
        $spreadsheetId = Config::get('sheets.spreadsheet_id');

        if (! $spreadsheetId) {
            throw new \RuntimeException('Spreadsheet ID is not configured.');
        }

        $service = $this->getSheetsService();
        $range = "{$sheetName}!A:A";

        $body = new Sheets\ValueRange([
            'values' => [$rowValues],
        ]);

        $params = [
            'valueInputOption' => 'USER_ENTERED',
        ];

        $service->spreadsheets_values->append($spreadsheetId, $range, $body, $params);
    }

    /**
     * Perform read-only connectivity test.
     */
    public function testConnection(): ConnectionTestResult
    {
        $config = Config::get('sheets');

        if (! ($config['enabled'] ?? false)) {
            return new ConnectionTestResult(
                success: false,
                message: 'Google Sheets integration is disabled.',
                errorCode: 'api_unavailable'
            );
        }

        $spreadsheetId = $config['spreadsheet_id'] ?? null;
        if (! $spreadsheetId) {
            return new ConnectionTestResult(
                success: false,
                message: 'Spreadsheet ID is missing.',
                errorCode: 'spreadsheet_not_found'
            );
        }

        try {
            $service = $this->getSheetsService();
            // Call read-only metadata check
            $spreadsheet = $service->spreadsheets->get($spreadsheetId);

            $title = $spreadsheet->getProperties()->getTitle();
            $sheets = $spreadsheet->getSheets();
            $sheetCount = is_array($sheets) ? count($sheets) : 0;

            return new ConnectionTestResult(
                success: true,
                message: 'Successfully connected to Google Sheets.',
                spreadsheetTitle: $title,
                sheetCount: $sheetCount
            );
        } catch (Throwable $e) {
            // Log real details server-side
            Log::error('Google Sheets connection test failed', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $errorCode = 'api_unavailable';
            $message = 'Unable to connect to Google Sheets.';

            // Categorize errors
            $errorMsg = $e->getMessage();
            if (str_contains($errorMsg, 'invalid_grant') || str_contains($errorMsg, 'decode') || str_contains($errorMsg, 'credentials') || str_contains($errorMsg, 'private_key')) {
                $errorCode = 'credentials_invalid';
                $message = 'Google Sheets credentials are invalid.';
            } elseif (str_contains($errorMsg, 'not found') || str_contains($errorMsg, 'Requested entity was not found') || $e->getCode() === 404) {
                $errorCode = 'spreadsheet_not_found';
                $message = 'Google Spreadsheet was not found or is inaccessible.';
            }

            return new ConnectionTestResult(
                success: false,
                message: $message,
                errorCode: $errorCode
            );
        }
    }
}
