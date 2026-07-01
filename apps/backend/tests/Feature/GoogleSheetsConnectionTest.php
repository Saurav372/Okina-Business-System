<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\GoogleSheets\GoogleSheetsClient;
use Database\Seeders\AccessControlSeeder;
use Google\Service\Exception;
use Google\Service\Sheets;
use Google\Service\Sheets\Resource\Spreadsheets;
use Google\Service\Sheets\Spreadsheet;
use Google\Service\Sheets\SpreadsheetProperties;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Mockery;
use Tests\TestCase;

class GoogleSheetsConnectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccessControlSeeder::class);
    }

    /**
     * Test configuration resolution.
     */
    public function test_config_resolution(): void
    {
        Config::set('sheets.enabled', true);
        Config::set('sheets.spreadsheet_id', 'sheet-123');
        Config::set('sheets.credentials.client_email', 'client@example.com');
        Config::set('sheets.credentials.private_key', 'key\nwith\nnewlines');
        Config::set('sheets.credentials.project_id', 'proj-123');

        $client = new GoogleSheetsClient;
        $googleClient = $client->getClient();

        // Verify that the scopes include the read-only spreadsheets scope
        $this->assertContains(Sheets::SPREADSHEETS_READONLY, $googleClient->getScopes());
    }

    /**
     * Test connection fails if missing credentials configuration.
     */
    public function test_fails_on_incomplete_credentials(): void
    {
        Config::set('sheets.credentials.client_email', null);

        $client = new GoogleSheetsClient;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Google Sheets configuration is incomplete.');

        $client->getClient();
    }

    /**
     * Test connectivity test returns error when disabled.
     */
    public function test_connection_test_returns_error_when_disabled(): void
    {
        Config::set('sheets.enabled', false);

        $client = new GoogleSheetsClient;
        $result = $client->testConnection();

        $this->assertFalse($result->success);
        $this->assertEquals('Google Sheets integration is disabled.', $result->message);
        $this->assertEquals('api_unavailable', $result->errorCode);
    }

    /**
     * Test connectivity test returns error when spreadsheet ID is missing.
     */
    public function test_connection_test_returns_error_when_spreadsheet_id_missing(): void
    {
        Config::set('sheets.enabled', true);
        Config::set('sheets.spreadsheet_id', null);

        $client = new GoogleSheetsClient;
        $result = $client->testConnection();

        $this->assertFalse($result->success);
        $this->assertEquals('Spreadsheet ID is missing.', $result->message);
        $this->assertEquals('spreadsheet_not_found', $result->errorCode);
    }

    /**
     * Test successful connectivity test using mocked sheets service.
     */
    public function test_successful_connection_test(): void
    {
        Config::set('sheets.enabled', true);
        Config::set('sheets.spreadsheet_id', 'mock-sheet-id');

        $mockClient = $this->getMockBuilder(GoogleSheetsClient::class)
            ->onlyMethods(['getSheetsService'])
            ->getMock();

        $mockSheetsService = Mockery::mock(Sheets::class);
        $mockSpreadsheets = Mockery::mock(Spreadsheets::class);
        $mockSheetsService->spreadsheets = $mockSpreadsheets;

        $mockSpreadsheet = Mockery::mock(Spreadsheet::class);
        $mockProperties = Mockery::mock(SpreadsheetProperties::class);
        $mockProperties->shouldReceive('getTitle')->andReturn('Okina Craft Sync');
        $mockSpreadsheet->shouldReceive('getProperties')->andReturn($mockProperties);
        $mockSpreadsheet->shouldReceive('getSheets')->andReturn([new \stdClass, new \stdClass]);

        $mockSpreadsheets->shouldReceive('get')
            ->with('mock-sheet-id')
            ->once()
            ->andReturn($mockSpreadsheet);

        $mockClient->expects($this->once())
            ->method('getSheetsService')
            ->willReturn($mockSheetsService);

        $result = $mockClient->testConnection();

        $this->assertTrue($result->success);
        $this->assertEquals('Successfully connected to Google Sheets.', $result->message);
        $this->assertEquals('Okina Craft Sync', $result->spreadsheetTitle);
        $this->assertEquals(2, $result->sheetCount);
        $this->assertNull($result->errorCode);
    }

    /**
     * Test connectivity test exception handling for invalid credentials.
     */
    public function test_connection_test_invalid_credentials_exception(): void
    {
        Config::set('sheets.enabled', true);
        Config::set('sheets.spreadsheet_id', 'mock-sheet-id');

        $mockClient = $this->getMockBuilder(GoogleSheetsClient::class)
            ->onlyMethods(['getSheetsService'])
            ->getMock();

        $mockSheetsService = Mockery::mock(Sheets::class);
        $mockSpreadsheets = Mockery::mock(Spreadsheets::class);
        $mockSheetsService->spreadsheets = $mockSpreadsheets;

        $mockSpreadsheets->shouldReceive('get')
            ->with('mock-sheet-id')
            ->once()
            ->andThrow(new Exception('invalid_grant', 400));

        $mockClient->expects($this->once())
            ->method('getSheetsService')
            ->willReturn($mockSheetsService);

        $result = $mockClient->testConnection();

        $this->assertFalse($result->success);
        $this->assertEquals('Google Sheets credentials are invalid.', $result->message);
        $this->assertEquals('credentials_invalid', $result->errorCode);
    }

    /**
     * Test connectivity test exception handling for spreadsheet not found.
     */
    public function test_connection_test_spreadsheet_not_found_exception(): void
    {
        Config::set('sheets.enabled', true);
        Config::set('sheets.spreadsheet_id', 'mock-sheet-id');

        $mockClient = $this->getMockBuilder(GoogleSheetsClient::class)
            ->onlyMethods(['getSheetsService'])
            ->getMock();

        $mockSheetsService = Mockery::mock(Sheets::class);
        $mockSpreadsheets = Mockery::mock(Spreadsheets::class);
        $mockSheetsService->spreadsheets = $mockSpreadsheets;

        $mockSpreadsheets->shouldReceive('get')
            ->with('mock-sheet-id')
            ->once()
            ->andThrow(new Exception('Requested entity was not found', 404));

        $mockClient->expects($this->once())
            ->method('getSheetsService')
            ->willReturn($mockSheetsService);

        $result = $mockClient->testConnection();

        $this->assertFalse($result->success);
        $this->assertEquals('Google Spreadsheet was not found or is inaccessible.', $result->message);
        $this->assertEquals('spreadsheet_not_found', $result->errorCode);
    }

    /**
     * Test administrative endpoint authentication and authorization.
     */
    public function test_admin_endpoint_authorization_boundaries(): void
    {
        // 1. Guest access denied (302 redirect)
        $this->postJson(route('admin.google_sheets.test_connection'))
            ->assertStatus(302);

        // 2. Unauthorized roles (Sales Staff) return 403
        $salesUser = User::factory()->create(['user_type' => User::TYPE_STAFF]);
        $salesUser->assignRole(Role::SALES_STAFF);

        $this->actingAs($salesUser)
            ->postJson(route('admin.google_sheets.test_connection'))
            ->assertStatus(403);

        // 3. Super Admin with settings.manage allowed
        $superAdmin = User::factory()->create(['user_type' => User::TYPE_STAFF]);
        $superAdmin->assignRole(Role::SUPER_ADMIN);

        // Configure client so endpoint doesn't fail internally on instantiation
        Config::set('sheets.enabled', false);

        $this->actingAs($superAdmin)
            ->postJson(route('admin.google_sheets.test_connection'))
            ->assertStatus(200)
            ->assertJson([
                'success' => false,
                'message' => 'Google Sheets integration is disabled.',
                'error_code' => 'api_unavailable',
            ]);
    }
}
