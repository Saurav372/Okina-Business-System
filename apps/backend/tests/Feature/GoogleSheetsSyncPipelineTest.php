<?php
 
namespace Tests\Feature;
 
use App\Jobs\SyncRecordToGoogleSheetsJob;
use App\Models\Customer;
use App\Support\GoogleSheets\GoogleSheetsClient;
use App\Support\GoogleSheets\GoogleSheetsPayloadMapper;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;
 
class GoogleSheetsSyncPipelineTest extends TestCase
{
    use RefreshDatabase;
 
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccessControlSeeder::class);
    }
 
    public function test_saving_model_dispatches_sync_job_when_enabled(): void
    {
        Config::set('sheets.enabled', true);
        Bus::fake();
 
        $customer = Customer::factory()->create([
            'display_name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
            'status' => 'active',
        ]);
 
        Bus::assertDispatched(SyncRecordToGoogleSheetsJob::class, function ($job) use ($customer) {
            return $job->uniqueId() === 'sync_record_to_google_sheets_job:app-models-customer:'.$customer->id;
        });
    }
 
    /**
     * Test saving a supported model does NOT queue the job when disabled.
     */
    public function test_saving_model_does_not_dispatch_job_when_disabled(): void
    {
        Config::set('sheets.enabled', false);
        Bus::fake();
 
        Customer::factory()->create();
 
        DB::commit();
 
        Bus::assertNotDispatched(SyncRecordToGoogleSheetsJob::class);
    }
 
    /**
     * Test transaction commit dispatching.
     */
    public function test_job_dispatched_only_after_transaction_commits(): void
    {
        Config::set('sheets.enabled', true);
        Bus::fake();
 
        DB::beginTransaction();
 
        $customer = Customer::factory()->create();
 
        // Within transaction, not yet committed, should not be dispatched yet
        Bus::assertNotDispatched(SyncRecordToGoogleSheetsJob::class);
 
        DB::commit();
 
        Bus::assertDispatched(SyncRecordToGoogleSheetsJob::class);
    }
 
    /**
     * Test transaction rollback does NOT dispatch the job.
     */
    public function test_job_not_dispatched_if_transaction_rolls_back(): void
    {
        Config::set('sheets.enabled', true);
        Bus::fake();
 
        DB::beginTransaction();
 
        $customer = Customer::factory()->create();
 
        DB::rollBack();
 
        Bus::assertNotDispatched(SyncRecordToGoogleSheetsJob::class);
    }
 
    /**
     * Test job execution correctly maps model and calls syncRow.
     */
    public function test_job_maps_payload_and_calls_sync_row(): void
    {
        Config::set('sheets.enabled', true);
 
        $customer = Customer::factory()->create([
            'display_name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'phone' => '0987654321',
            'status' => 'active',
        ]);
 
        $mockClient = Mockery::mock(GoogleSheetsClient::class);
        $mockClient->shouldReceive('syncRow')
            ->once()
            ->with(
                'Customers',
                ['public_id', 'display_name', 'email', 'phone', 'status', 'created_at'],
                Mockery::on(function ($values) use ($customer) {
                    return $values[0] == $customer->public_id && $values[1] === 'Jane Smith';
                }),
                'public_id',
                (string) $customer->public_id
            );
 
        $this->app->instance(GoogleSheetsClient::class, $mockClient);
 
        // Run the job
        $job = new SyncRecordToGoogleSheetsJob(Customer::class, $customer->id);
        $this->app->call([$job, 'handle']);
    }
 
    /**
     * Test reordered columns configuration verifies that syncRow gets correct column and index.
     */
    public function test_reordered_columns_still_determines_correct_column_letter(): void
    {
        Config::set('sheets.enabled', true);
 
        // Reorder columns so unique_key is NOT first
        Config::set('sheets.entities.'.Customer::class.'.columns', [
            'display_name' => 'Name',
            'email' => 'Email',
            'public_id' => 'Customer ID', // unique_key 'public_id' is now 3rd (index 2, which corresponds to Column C)
        ]);
 
        $customer = Customer::factory()->create([
            'display_name' => 'Alice Cooper',
            'email' => 'alice@example.com',
        ]);
 
        $mockClient = Mockery::mock(GoogleSheetsClient::class);
 
        // Assert that syncRow receives correct parameters
        $mockClient->shouldReceive('syncRow')
            ->once()
            ->with(
                'Customers',
                ['display_name', 'email', 'public_id'],
                ['Alice Cooper', 'alice@example.com', (string) $customer->public_id],
                'public_id',
                (string) $customer->public_id
            )
            ->andReturnUsing(function ($sheet, $columnKeys, $rowValues, $key, $value) {
                // Manually run the column letter calculation to ensure it resolves to 'C' (3rd column)
                $colIndex = array_search($key, $columnKeys, true);
                if ($colIndex !== 2) {
                    throw new \Exception("Expected column index 2, got {$colIndex}");
                }
                // Let's call getColumnLetter to verify
                $client = new GoogleSheetsClient;
                $columnLetter = $client->getColumnLetter((int) $colIndex);
                if ($columnLetter !== 'C') {
                    throw new \Exception("Expected column letter 'C', got {$columnLetter}");
                }
            });
 
        $this->app->instance(GoogleSheetsClient::class, $mockClient);
 
        $job = new SyncRecordToGoogleSheetsJob(Customer::class, $customer->id);
        $this->app->call([$job, 'handle']);
    }
 
    /**
     * Test job fails immediately when unique key configuration is missing/invalid.
     */
    public function test_missing_or_invalid_unique_key_fails_job_immediately(): void
    {
        Config::set('sheets.enabled', true);
 
        // Set unique_key to a field that doesn't exist in columns
        Config::set('sheets.entities.'.Customer::class.'.unique_key', 'invalid_field_name');
 
        $customer = Customer::factory()->create();
 
        $mockClient = Mockery::mock(GoogleSheetsClient::class);
        $mockClient->shouldNotReceive('syncRow');
        $this->app->instance(GoogleSheetsClient::class, $mockClient);
 
        $job = new TestSyncRecordToGoogleSheetsJob(Customer::class, $customer->id);
        $this->app->call([$job, 'handle']);
 
        $this->assertTrue($job->failedCalled);
        $this->assertInstanceOf(\InvalidArgumentException::class, $job->failedException);
        $this->assertStringContainsString('unique_key configuration is missing or invalid', $job->failedException->getMessage());
    }
 
    /**
     * Test job fails immediately when the resolved unique value is empty/null/whitespace.
     */
    public function test_empty_unique_value_fails_job_immediately(): void
    {
        Config::set('sheets.enabled', true);
 
        // Save a customer, then we will stub the mapper to return an empty unique value
        $customer = Customer::factory()->create();
 
        $mockClient = Mockery::mock(GoogleSheetsClient::class);
        $mockClient->shouldNotReceive('syncRow');
        $this->app->instance(GoogleSheetsClient::class, $mockClient);
 
        // Stub the mapper to return an empty string for the unique key field
        $mockMapper = Mockery::mock(GoogleSheetsPayloadMapper::class)->makePartial();
        $mockMapper->shouldReceive('map')->andReturn([
            'public_id' => ' ', // whitespace only, should trigger trim check
            'display_name' => 'John Doe',
        ]);
        $mockMapper->shouldReceive('sheet')->andReturn('Customers');
        $mockMapper->shouldReceive('headers')->andReturn(['Customer ID', 'Name']);
        $this->app->instance(GoogleSheetsPayloadMapper::class, $mockMapper);
 
        $job = new TestSyncRecordToGoogleSheetsJob(Customer::class, $customer->id);
        $this->app->call([$job, 'handle']);
 
        $this->assertTrue($job->failedCalled);
        $this->assertInstanceOf(\InvalidArgumentException::class, $job->failedException);
        $this->assertStringContainsString('Resolved unique value is empty', $job->failedException->getMessage());
    }
 
    /**
     * Test transient errors trigger job retries.
     */
    public function test_transient_error_rethrows_exception_to_trigger_retry(): void
    {
        Config::set('sheets.enabled', true);
 
        $customer = Customer::factory()->create();
 
        $mockClient = Mockery::mock(GoogleSheetsClient::class);
        // Simulate a Google API 429 rate limit exception
        $mockClient->shouldReceive('syncRow')->andThrow(new \Exception('Quota exceeded', 429));
        $this->app->instance(GoogleSheetsClient::class, $mockClient);
 
        $job = new SyncRecordToGoogleSheetsJob(Customer::class, $customer->id);
 
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Quota exceeded');
 
        $job->handle(
            $this->app->make(GoogleSheetsClient::class),
            $this->app->make(GoogleSheetsPayloadMapper::class)
        );
    }
 
    /**
     * Test permanent errors fail the job immediately.
     */
    public function test_permanent_error_fails_job_immediately_without_rethrowing(): void
    {
        Config::set('sheets.enabled', true);
 
        $customer = Customer::factory()->create();
 
        $mockClient = Mockery::mock(GoogleSheetsClient::class);
        // Simulate a Google API 404/not found permanent exception
        $mockClient->shouldReceive('syncRow')->andThrow(new \Exception('Spreadsheet not found', 404));
        $this->app->instance(GoogleSheetsClient::class, $mockClient);
 
        $job = new TestSyncRecordToGoogleSheetsJob(Customer::class, $customer->id);
        $this->app->call([$job, 'handle']);
 
        $this->assertTrue($job->failedCalled);
        $this->assertEquals('Spreadsheet not found', $job->failedException->getMessage());
    }
 
    /**
     * Test the column letter conversion helper with various indexes.
     */
    public function test_column_letter_conversion_helper(): void
    {
        $client = new GoogleSheetsClient;
 
        $this->assertSame('A', $client->getColumnLetter(0));
        $this->assertSame('Z', $client->getColumnLetter(25));
        $this->assertSame('AA', $client->getColumnLetter(26));
        $this->assertSame('AZ', $client->getColumnLetter(51));
        $this->assertSame('BA', $client->getColumnLetter(52));
        $this->assertSame('ZZ', $client->getColumnLetter(701));
        $this->assertSame('AAA', $client->getColumnLetter(702));
    }
}
 
/**
 * Helper class for testing job failure without complex mockery.
 */
class TestSyncRecordToGoogleSheetsJob extends SyncRecordToGoogleSheetsJob
{
    public bool $failedCalled = false;
 
    public ?\Throwable $failedException = null;
 
    public function fail($exception = null): void
    {
        $this->failedCalled = true;
        $this->failedException = $exception;
        // Don't call parent::fail() to avoid logging or database operations in this test context
    }
}
