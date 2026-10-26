<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_article_versions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('knowledge_article_id')->constrained('knowledge_articles')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('event', 40);
            $table->enum('status', ['draft', 'review', 'published', 'inactive']);
            $table->string('title', 180);
            $table->string('summary', 500);
            $table->text('content');
            $table->string('category', 100)->nullable();
            $table->string('audience', 80);
            $table->string('source_reference', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('staff_admins')->nullOnDelete();
            $table->string('created_by_name', 180)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('staff_admins')->nullOnDelete();
            $table->string('reviewed_by_name', 180)->nullable();
            $table->foreignId('acted_by')->nullable()->constrained('staff_admins')->nullOnDelete();
            $table->string('acted_by_name', 180)->nullable();
            $table->boolean('publication_confirmed')->default(false);
            $table->timestamp('publication_confirmed_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('created_at');
            $table->unique(['knowledge_article_id', 'version']);
            $table->index(['knowledge_article_id', 'status']);
        });

        Schema::table('knowledge_articles', function (Blueprint $table): void {
            $table->boolean('publication_active')->default(false);
            $table->foreignUuid('published_version_id')
                ->nullable()
                ->constrained('knowledge_article_versions')
                ->nullOnDelete();
        });

        // Preserve only content that was already published before versioning.
        // Draft/review legacy records remain unpublished and require an explicit
        // human review and publication transition.
        DB::table('knowledge_articles')
            ->where('status', 'published')
            ->orderBy('id')
            ->chunkById(100, function ($articles): void {
                foreach ($articles as $article) {
                    $versionId = (string) Str::uuid();
                    $publishedAt = $article->published_at ?? $article->updated_at ?? $article->created_at ?? now()->utc();

                    DB::table('knowledge_article_versions')->insert([
                        'id' => $versionId,
                        'knowledge_article_id' => $article->id,
                        'version' => 1,
                        'event' => 'legacy_publication_preserved',
                        'status' => 'published',
                        'title' => $article->title,
                        'summary' => $article->summary,
                        'content' => $article->content,
                        'category' => $article->category,
                        'audience' => $article->audience,
                        'source_reference' => $article->source_reference,
                        'created_by' => null,
                        'created_by_name' => null,
                        'reviewed_by' => null,
                        'reviewed_by_name' => null,
                        'acted_by' => null,
                        'acted_by_name' => null,
                        'publication_confirmed' => false,
                        'publication_confirmed_at' => null,
                        'published_at' => $article->published_at,
                        'created_at' => $publishedAt,
                    ]);

                    DB::table('knowledge_articles')->where('id', $article->id)->update([
                        'published_version_id' => $versionId,
                        'publication_active' => true,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('knowledge_articles', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('published_version_id');
            $table->dropColumn('publication_active');
        });

        Schema::dropIfExists('knowledge_article_versions');
    }
};
