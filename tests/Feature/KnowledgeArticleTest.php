<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeArticleVersion;
use App\Models\StaffAccessScope;
use App\Models\StaffAdmin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class KnowledgeArticleTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_feed_exposes_only_public_published_articles(): void
    {
        $this->published('Como acompanhar', 'public');
        $this->published('Orientação autenticada', 'authenticated');
        $this->published('Nota interna', 'internal');
        KnowledgeArticle::create(['title' => 'Rascunho', 'summary' => 'Ainda privado', 'content' => 'Conteúdo interno', 'status' => 'review', 'audience' => 'public']);

        $this->getJson('/api/knowledge-articles')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.title', 'Como acompanhar');
    }

    public function test_authenticated_feed_exposes_only_authenticated_published_articles_to_students(): void
    {
        $this->published('Como acompanhar', 'public');
        $this->published('Orientação autenticada', 'authenticated');
        $this->published('Nota interna', 'internal');
        $student = $this->user('student', 'article-student@example.test', '90000008801', 'QA-8801');

        $this->actingAs($student, 'api')->getJson('/api/knowledge-articles/authenticated')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.title', 'Orientação autenticada');

        $this->getJson('/api/knowledge-articles')->assertOk()->assertJsonCount(1)->assertJsonPath('0.title', 'Como acompanhar');
    }

    public function test_authenticated_feed_rejects_staff_and_unauthenticated_access(): void
    {
        $this->getJson('/api/knowledge-articles/authenticated')->assertUnauthorized();
        $staff = $this->user('staff', 'article-feed-staff@example.test', '90000008802', 'QA-8802');
        $this->actingAs($staff, 'api')->getJson('/api/knowledge-articles/authenticated')->assertForbidden();
    }

    public function test_new_article_must_start_as_draft_and_has_an_immutable_version(): void
    {
        $actor = $this->staff('cradt', 'article-create@example.test', '90000008803');
        $this->actingAs($actor, 'staff_admins')->postJson('/api/admin/knowledge-articles', $this->payload('review'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $created = $this->actingAs($actor, 'staff_admins')->postJson('/api/admin/knowledge-articles', $this->payload('draft'))
            ->assertCreated()
            ->assertJsonPath('status', 'draft');
        $version = KnowledgeArticleVersion::query()->where('knowledge_article_id', $created->json('id'))->firstOrFail();
        $this->assertSame(1, $version->version);
        $this->assertSame('draft', $version->status);
        $this->assertSame($actor->id, $version->created_by);
        $this->assertSame($actor->id, $version->acted_by);
        $this->assertSame('Fonte de teste', $version->source_reference);
        $this->assertNotNull($version->created_at);
        $this->assertNull($version->reviewed_by);

        try {
            $version->update(['content' => 'Tentativa de adulterar o histórico']);
            $this->fail('A versão imutável não deveria aceitar atualização.');
        } catch (LogicException $exception) {
            $this->assertSame('Versões de artigos são imutáveis.', $exception->getMessage());
        }

        try {
            $version->delete();
            $this->fail('A versão imutável não deveria aceitar remoção.');
        } catch (LogicException $exception) {
            $this->assertSame('Versões de artigos não podem ser removidas pela aplicação.', $exception->getMessage());
        }
    }

    public function test_cradt_submits_review_and_global_admin_publishes_a_separate_version(): void
    {
        $author = $this->staff('cradt', 'article-author@example.test', '90000008804');
        $publisher = $this->staff('admin', 'article-publisher@example.test', '90000008805');
        $created = $this->actingAs($author, 'staff_admins')->postJson('/api/admin/knowledge-articles', $this->payload('draft'))->assertCreated();
        $id = $created->json('id');

        $this->actingAs($author, 'staff_admins')->putJson('/api/admin/knowledge-articles/'.$id, $this->payload('review'))
            ->assertOk()
            ->assertJsonPath('status', 'review');

        $this->actingAs($author, 'staff_admins')->putJson('/api/admin/knowledge-articles/'.$id, $this->payload('published', true))
            ->assertForbidden();

        $published = $this->actingAs($publisher, 'staff_admins')->putJson('/api/admin/knowledge-articles/'.$id, $this->payload('published', true))
            ->assertOk()
            ->assertJsonPath('status', 'published')
            ->assertJsonPath('can_publish', true);

        $versions = KnowledgeArticleVersion::query()->where('knowledge_article_id', $id)->orderBy('version')->get();
        $this->assertCount(3, $versions);
        $this->assertSame(['draft', 'review', 'published'], $versions->pluck('status')->all());
        $this->assertSame($author->id, $versions[1]->created_by);
        $this->assertSame($publisher->id, $versions[2]->reviewed_by);
        $this->assertSame($publisher->id, $versions[2]->acted_by);
        $this->assertSame('published', $versions[2]->event);
        $this->assertTrue($versions[2]->publication_confirmed);
        $this->assertNotNull($versions[2]->publication_confirmed_at);
        $this->assertNotNull($published->json('published_version_id'));
        $this->assertSame($versions[2]->id, $published->json('published_version_id'));
    }

    public function test_reference_text_alone_does_not_authorize_publication_and_skipped_transition_is_rejected(): void
    {
        $admin = $this->staff('admin', 'article-transition@example.test', '90000008806');
        $created = $this->actingAs($admin, 'staff_admins')->postJson('/api/admin/knowledge-articles', $this->payload('draft'))->assertCreated();
        $id = $created->json('id');

        $this->actingAs($admin, 'staff_admins')->putJson('/api/admin/knowledge-articles/'.$id, $this->payload('published', true))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
        $this->assertSame('draft', KnowledgeArticle::findOrFail($id)->status);

        $this->actingAs($admin, 'staff_admins')->putJson('/api/admin/knowledge-articles/'.$id, $this->payload('review'))->assertOk();
        $this->actingAs($admin, 'staff_admins')->putJson('/api/admin/knowledge-articles/'.$id, $this->payload('published'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('publication_confirmed');
        $this->assertSame('review', KnowledgeArticle::findOrFail($id)->status);
    }

    public function test_scoped_admin_cannot_publish_or_deactivate(): void
    {
        $author = $this->staff('cradt', 'article-scope-author@example.test', '90000008807');
        $scopedAdmin = $this->staff('admin', 'article-scoped-admin@example.test', '90000008808');
        StaffAccessScope::create([
            'staff_admin_id' => $scopedAdmin->id,
            'sector' => 'CRADT',
            'abilities' => ['manage_knowledge'],
            'reason' => 'Escopo de QA fictício.',
            'granted_by' => $author->id,
        ]);
        $created = $this->actingAs($author, 'staff_admins')->postJson('/api/admin/knowledge-articles', $this->payload('draft'))->assertCreated();
        $id = $created->json('id');
        $this->actingAs($author, 'staff_admins')->putJson('/api/admin/knowledge-articles/'.$id, $this->payload('review'))->assertOk();

        $this->actingAs($scopedAdmin, 'staff_admins')->putJson('/api/admin/knowledge-articles/'.$id, $this->payload('published', true))->assertForbidden();
        $this->actingAs($scopedAdmin, 'staff_admins')->putJson('/api/admin/knowledge-articles/'.$id, $this->payload('inactive'))->assertForbidden();
        $this->assertFalse($this->getJson('/api/admin/knowledge-articles')->json('data.0.can_publish'));
    }

    public function test_pending_revision_never_overwrites_the_current_published_public_version(): void
    {
        $admin = $this->staff('admin', 'article-live-version@example.test', '90000008809');
        $created = $this->actingAs($admin, 'staff_admins')->postJson('/api/admin/knowledge-articles', $this->payload('draft'))->assertCreated();
        $id = $created->json('id');
        $this->actingAs($admin, 'staff_admins')->putJson('/api/admin/knowledge-articles/'.$id, $this->payload('review'))->assertOk();
        $this->actingAs($admin, 'staff_admins')->putJson('/api/admin/knowledge-articles/'.$id, $this->payload('published', true))->assertOk();

        $revision = $this->payload('draft');
        $revision['title'] = 'Nova versão em revisão';
        $revision['content'] = 'Conteúdo novo ainda não aprovado.';
        $this->actingAs($admin, 'staff_admins')->putJson('/api/admin/knowledge-articles/'.$id, $revision)->assertOk();
        $revision['status'] = 'review';
        $this->actingAs($admin, 'staff_admins')->putJson('/api/admin/knowledge-articles/'.$id, $revision)->assertOk();

        $this->getJson('/api/knowledge-articles')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.title', 'Artigo de teste')
            ->assertJsonPath('0.content', 'Conteúdo fictício de QA.');
        $adminList = $this->actingAs($admin, 'staff_admins')->getJson('/api/admin/knowledge-articles')->assertOk();
        $this->assertSame('Nova versão em revisão', $adminList->json('data.0.title'));
        $this->assertSame('Artigo de teste', $adminList->json('data.0.published_version.title'));
        $this->assertSame(5, count($adminList->json('data.0.versions')));
    }

    public function test_editing_a_legacy_publication_preserves_its_live_snapshot_without_creating_a_review(): void
    {
        $legacy = $this->published('Publicação legada', 'public');
        $admin = $this->staff('cradt', 'article-legacy-editor@example.test', '90000008814');
        $draft = [
            'title' => 'Nova revisão legada',
            'summary' => 'Resumo novo pendente',
            'content' => 'Texto novo ainda não revisado.',
            'category' => 'QA',
            'audience' => 'public',
            'source_reference' => 'Referência informativa',
            'status' => 'draft',
        ];

        $this->actingAs($admin, 'staff_admins')->putJson('/api/admin/knowledge-articles/'.$legacy->id, $draft)
            ->assertOk()
            ->assertJsonPath('status', 'draft')
            ->assertJsonPath('published_version.title', 'Publicação legada');

        $this->getJson('/api/knowledge-articles')->assertOk()->assertJsonCount(1)->assertJsonPath('0.title', 'Publicação legada');
        $versions = KnowledgeArticleVersion::query()->where('knowledge_article_id', $legacy->id)->orderBy('version')->get();
        $this->assertSame('legacy_publication_preserved', $versions[0]->event);
        $this->assertSame('published', $versions[0]->status);
        $this->assertNull($versions[0]->created_by);
        $this->assertNull($versions[0]->reviewed_by);
        $this->assertSame('draft', $versions[1]->status);
        $this->assertNotNull($versions[1]->created_by);
    }

    public function test_admin_can_deactivate_but_history_and_publication_snapshot_remain(): void
    {
        $admin = $this->staff('admin', 'article-deactivate@example.test', '90000008810');
        $created = $this->actingAs($admin, 'staff_admins')->postJson('/api/admin/knowledge-articles', $this->payload('draft'))->assertCreated();
        $id = $created->json('id');
        $this->actingAs($admin, 'staff_admins')->putJson('/api/admin/knowledge-articles/'.$id, $this->payload('review'))->assertOk();
        $this->actingAs($admin, 'staff_admins')->putJson('/api/admin/knowledge-articles/'.$id, $this->payload('published', true))->assertOk();
        $this->actingAs($admin, 'staff_admins')->putJson('/api/admin/knowledge-articles/'.$id, $this->payload('inactive'))->assertOk()->assertJsonPath('status', 'inactive');

        $this->getJson('/api/knowledge-articles')->assertOk()->assertJsonCount(0);
        $this->assertSame(4, KnowledgeArticleVersion::query()->where('knowledge_article_id', $id)->count());
        $this->assertSame('published', KnowledgeArticleVersion::query()->where('id', KnowledgeArticle::findOrFail($id)->published_version_id)->value('status'));

        $revised = $this->payload('draft');
        $revised['title'] = 'Nova revisão do artigo desativado';
        $this->actingAs($admin, 'staff_admins')->putJson('/api/admin/knowledge-articles/'.$id, $revised)
            ->assertOk()
            ->assertJsonPath('publication_active', false);
        $this->actingAs($admin, 'staff_admins')->putJson('/api/admin/knowledge-articles/'.$id, [...$revised, 'status' => 'review'])->assertOk();
        $this->getJson('/api/knowledge-articles')->assertOk()->assertJsonCount(0);

        $this->actingAs($admin, 'staff_admins')->putJson('/api/admin/knowledge-articles/'.$id, [...$revised, 'status' => 'published', 'publication_confirmed' => true])
            ->assertOk()
            ->assertJsonPath('publication_active', true);
        $this->getJson('/api/knowledge-articles')->assertOk()->assertJsonCount(1)->assertJsonPath('0.title', 'Nova revisão do artigo desativado');
    }

    public function test_admin_article_listing_is_not_available_to_students_or_technical_staff(): void
    {
        $this->getJson('/api/admin/knowledge-articles')->assertUnauthorized();
        $technical = $this->staff('staff', 'article-tech@example.test', '90000008811');
        $this->actingAs($technical, 'staff_admins')->getJson('/api/admin/knowledge-articles')->assertForbidden();
    }

    public function test_inactive_global_admin_cannot_publish(): void
    {
        $author = $this->staff('cradt', 'article-inactive-author@example.test', '90000008812');
        $inactiveAdmin = $this->staff('admin', 'article-inactive-admin@example.test', '90000008813', false);
        $created = $this->actingAs($author, 'staff_admins')->postJson('/api/admin/knowledge-articles', $this->payload('draft'))->assertCreated();
        $id = $created->json('id');
        $this->actingAs($author, 'staff_admins')->putJson('/api/admin/knowledge-articles/'.$id, $this->payload('review'))->assertOk();
        $this->actingAs($inactiveAdmin, 'staff_admins')->putJson('/api/admin/knowledge-articles/'.$id, $this->payload('published', true))->assertForbidden();
    }

    private function published(string $title, string $audience): KnowledgeArticle
    {
        return KnowledgeArticle::create([
            'title' => $title,
            'summary' => 'Resumo fictício',
            'content' => 'Orientação de teste.',
            'status' => 'published',
            'audience' => $audience,
            'source_reference' => 'Fonte de QA; não indica aprovação institucional.',
            'published_at' => now(),
        ]);
    }

    private function staff(string $role, string $email, string $cpf, bool $active = true): StaffAdmin
    {
        return StaffAdmin::create([
            'name' => strtoupper($role).' QA',
            'email' => $email,
            'cpf' => $cpf,
            'role' => $role,
            'password' => 'secret',
            'must_change_password' => false,
            'is_active' => $active,
        ]);
    }

    private function user(string $role, string $email, string $cpf, string $matricula): User
    {
        $course = Course::create(['name' => 'Curso QA '.$matricula, 'code' => 'QA-'.$matricula]);

        return User::create([
            'name' => strtoupper($role).' QA',
            'email' => $email,
            'cpf' => $cpf,
            'phone' => '00000000000',
            'matricula' => $matricula,
            'course_id' => $course->id,
            'role' => $role,
            'birthday' => '2000-01-01',
            'password' => 'secret',
        ]);
    }

    private function payload(string $status, bool $publicationConfirmed = false): array
    {
        return [
            'title' => 'Artigo de teste',
            'summary' => 'Resumo fictício de teste',
            'content' => 'Conteúdo fictício de QA.',
            'category' => 'QA',
            'audience' => 'public',
            'source_reference' => 'Fonte de teste',
            'status' => $status,
            ...($publicationConfirmed ? ['publication_confirmed' => true] : []),
        ];
    }
}
