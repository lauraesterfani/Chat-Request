<?php

namespace Tests\Feature;

use App\Models\KnowledgeArticle;
use App\Models\StaffAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeArticleTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_search_exposes_only_published_public_articles(): void
    {
        KnowledgeArticle::create(['title' => 'Como acompanhar', 'summary' => 'Texto público', 'content' => 'Orientação fictícia.', 'status' => 'published', 'audience' => 'public', 'published_at' => now()]);
        KnowledgeArticle::create(['title' => 'Nota interna', 'summary' => 'Não expor', 'content' => 'Interno.', 'status' => 'review', 'audience' => 'internal']);

        $this->getJson('/api/knowledge-articles?q=acompanhar')->assertOk()->assertJsonCount(1)->assertJsonPath('0.title', 'Como acompanhar');
    }

    public function test_admin_can_create_draft_article(): void
    {
        $admin = StaffAdmin::create(['name' => 'Admin', 'email' => 'article@example.test', 'cpf' => '90000007771', 'role' => 'admin', 'password' => 'secret', 'must_change_password' => false]);
        $this->actingAs($admin, 'staff_admins')->postJson('/api/admin/knowledge-articles', ['title' => 'Rascunho', 'summary' => 'Resumo fictício', 'content' => 'Conteúdo fictício', 'audience' => 'public', 'status' => 'draft'])->assertCreated();
    }
}
