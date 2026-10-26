<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Document;
use App\Models\FormSchemaVersion;
use App\Models\Request as StudentRequest;
use App\Models\RequestEvent;
use App\Models\TypeRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use LogicException;

/** Fictitious restore-drill fixture; guarded against every non-test environment. */
class Phase9RestoreFixtureSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('testing')) {
            throw new LogicException('Phase9RestoreFixtureSeeder só pode ser executado em APP_ENV=testing.');
        }

        $course = Course::create(['name' => 'Curso fictício de restauração', 'code' => 'RESTORE-QA']);
        $student = User::create([
            'name' => 'Solicitante fictício de restauração',
            'email' => 'restore.qa@example.test',
            'cpf' => '90000009876',
            'phone' => '81900009876',
            'matricula' => 'RESTOREQA001',
            'birthday' => '2000-01-01',
            'password' => 'RestoreTestOnly2026!',
            'role' => User::ROLE_STUDENT,
            'course_id' => $course->id,
            'must_change_password' => false,
        ]);
        $type = TypeRequest::create(['name' => 'Serviço fictício para restauração']);
        $schema = FormSchemaVersion::create([
            'type_request_id' => $type->id,
            'version' => 1,
            'status' => 'published',
            'schema' => ['fields' => [['key' => 'reason', 'label' => 'Motivo', 'type' => 'short_text']]],
            'change_summary' => 'Fixture técnica fictícia',
            'published_at' => now(),
        ]);
        $request = StudentRequest::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'type_id' => $type->id,
            'form_schema_version_id' => $schema->id,
            'subject' => 'Solicitação fictícia de restauração',
            'description' => 'Conteúdo sintético usado somente no ensaio isolado.',
            'form_responses' => ['reason' => 'Fixture sintética'],
            'status' => 'pending',
            'protocol' => '20261026-990001',
        ]);
        RequestEvent::create([
            'request_id' => $request->id,
            'event_type' => 'created',
            'actor_id' => $student->id,
            'actor_type' => 'student',
            'sector' => 'CRADT',
            'data' => ['status' => 'pending'],
            'created_at' => now(),
        ]);

        $relativePath = 'documents/phase9-restore-fixture.txt';
        Storage::disk('local')->put($relativePath, 'CHAT_REQUEST_PHASE9_RESTORE_FIXTURE_V1');
        $document = Document::create([
            'path' => $relativePath,
            'name' => 'fixture-restauracao.txt',
            'mime_type' => 'text/plain',
            'user_id' => $student->id,
            'file_size' => Storage::disk('local')->size($relativePath),
        ]);
        $request->documents()->sync([$document->id]);
    }
}
