<?php

namespace Tests\Feature;

use App\Models\AuditRecord;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class AuditAppendOnlyTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_records_cannot_be_updated_or_deleted_through_the_model(): void
    {
        $record = $this->record();

        try {
            $record->update(['action' => 'tampered']);
            $this->fail('Updating an audit record should be rejected.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('append-only', $exception->getMessage());
        }

        try {
            $record->delete();
            $this->fail('Deleting an audit record should be rejected.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('append-only', $exception->getMessage());
        }

        $this->assertDatabaseHas('audit_records', ['id' => $record->id, 'action' => 'created']);
    }

    public function test_database_rejects_direct_updates_and_deletes_of_audit_records(): void
    {
        $record = $this->record();

        try {
            AuditRecord::query()->whereKey($record->id)->toBase()->update(['action' => 'tampered']);
            $this->fail('Direct SQL update should be rejected by the database trigger.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('append-only', strtolower($exception->getMessage()));
        }

        try {
            AuditRecord::query()->whereKey($record->id)->toBase()->delete();
            $this->fail('Direct SQL delete should be rejected by the database trigger.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('append-only', strtolower($exception->getMessage()));
        }

        $this->assertDatabaseHas('audit_records', ['id' => $record->id, 'action' => 'created']);
    }

    private function record(): AuditRecord
    {
        return AuditRecord::create([
            'action' => 'created',
            'resource_type' => 'test',
            'resource_id' => 'fixture',
            'metadata' => ['state' => 'safe'],
            'created_at' => now(),
        ]);
    }
}
