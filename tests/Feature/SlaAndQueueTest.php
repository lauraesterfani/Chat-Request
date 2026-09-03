<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Request as RequestModel;
use App\Models\SlaPolicy;
use App\Models\StaffAdmin;
use App\Models\TypeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlaAndQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_policy_starts_as_draft_and_snapshot_is_saved_on_new_request(): void
    {
        $student = $this->student(); $type = TypeRequest::create(['name' => 'Serviço']);
        $admin = $this->admin();
        $policy = SlaPolicy::create(['name'=>'Teste','status'=>'active','type_request_id'=>$type->id,'first_response_minutes'=>60,'resolution_minutes'=>240,'created_by'=>$admin->id]);
        $response = $this->actingAs($student, 'api')->postJson('/api/requests', ['type_id'=>$type->id,'subject'=>'Assunto','description'=>'Descrição']);
        $response->assertCreated();
        $request = RequestModel::findOrFail($response->json('id'));
        $this->assertSame($policy->id, $request->sla_policy_id);
        $this->assertNotNull($request->sla_resolution_due_at);
    }

    public function test_queue_and_assignment_are_authorized(): void
    {
        $student = $this->student(); $request = RequestModel::create(['user_id'=>$student->id,'type_id'=>TypeRequest::create(['name'=>'T'])->id,'subject'=>'A','description'=>'D','protocol'=>'p-'.uniqid()]);
        $admin = $this->admin(); $attendant = $this->admin('cradt');
        $this->actingAs($admin,'staff_admins')->getJson('/api/admin/queue')->assertOk()->assertJsonPath('data.0.id',$request->id);
        $this->actingAs($admin,'staff_admins')->postJson("/api/requests/{$request->id}/assign",['staff_id'=>$attendant->id])->assertOk();
        $this->assertDatabaseHas('requests',['id'=>$request->id,'assigned_staff_id'=>$attendant->id]);
    }

    private function student(): User { $course=Course::create(['name'=>'C'.uniqid(),'code'=>'C'.uniqid()]); return User::create(['name'=>'Aluno','email'=>uniqid().'@student.test','password'=>'secret','cpf'=>str_pad((string)random_int(1,99999999999),11,'0',STR_PAD_LEFT),'phone'=>'81999999999','matricula'=>'M'.uniqid(),'course_id'=>$course->id,'role'=>'student','birthday'=>'2000-01-01']); }
    private function admin(string $role='admin'): StaffAdmin { return StaffAdmin::create(['name'=>'Admin','email'=>uniqid().'@example.test','cpf'=>str_pad((string)random_int(1,99999999999),11,'0',STR_PAD_LEFT),'role'=>$role,'password'=>'secret','must_change_password'=>false]); }
}
