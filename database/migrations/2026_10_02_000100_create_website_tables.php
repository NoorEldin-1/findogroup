<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->string('kind')->index();
            $table->string('source_system');
            $table->string('source_id');
            $table->string('status')->default('published')->index();
            $table->jsonb('source_data')->nullable();
            $table->timestampTz('published_at')->nullable();
            $table->timestampTz('source_updated_at')->nullable();
            $table->timestampsTz();
            $table->unique(['source_system', 'source_id']);
        });
        Schema::create('content_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5);
            $table->text('title');
            $table->text('excerpt')->nullable();
            $table->text('body_html');
            $table->jsonb('seo');
            $table->jsonb('view_data')->nullable();
            $table->timestampsTz();
            $table->unique(['content_id', 'locale']);
        });
        Schema::create('content_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained()->cascadeOnDelete();
            $table->string('path', 2048)->unique();
            $table->boolean('indexable')->default(true);
            $table->timestampsTz();
        });
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('source_path', 2048)->unique();
            $table->text('target_path');
            $table->unsignedSmallInteger('status')->default(301);
            $table->timestampsTz();
        });
        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->text('original_url');
            $table->text('path');
            $table->string('sha256', 64)->index();
            $table->string('mime');
            $table->unsignedBigInteger('bytes');
            $table->text('alt')->nullable();
            $table->timestampsTz();
        });
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('form_key')->index();
            $table->text('source_path');
            $table->jsonb('payload');
            $table->string('status')->default('received_locally');
            $table->timestampsTz();
        });
        Schema::create('import_runs', function (Blueprint $table) {
            $table->id();
            $table->string('source');
            $table->string('checksum', 64);
            $table->jsonb('summary');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        foreach (['import_runs', 'form_submissions', 'media_assets', 'redirects', 'content_routes', 'content_translations', 'contents'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
