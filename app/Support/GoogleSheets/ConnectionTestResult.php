<?php

namespace App\Support\GoogleSheets;

use JsonSerializable;

class ConnectionTestResult implements JsonSerializable
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly ?string $spreadsheetTitle = null,
        public readonly ?int $sheetCount = null,
        public readonly ?string $errorCode = null
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'spreadsheet_title' => $this->spreadsheetTitle,
            'sheet_count' => $this->sheetCount,
            'error_code' => $this->errorCode,
        ];
    }
}
