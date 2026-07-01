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

        // Minimum required scope for metadata inspection
        $client->setScopes([Sheets::SPREADSHEETS_READONLY]);

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
