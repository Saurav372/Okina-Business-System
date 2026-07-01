<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\GoogleSheets\GoogleSheetsClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoogleSheetsConnectionController extends Controller
{
    /**
     * Test connection to Google Sheets.
     */
    public function testConnection(Request $request, GoogleSheetsClient $client): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->hasPermissionTo('settings.manage')) {
            abort(403, 'This action is unauthorized.');
        }

        $result = $client->testConnection();

        return response()->json($result);
    }
}
