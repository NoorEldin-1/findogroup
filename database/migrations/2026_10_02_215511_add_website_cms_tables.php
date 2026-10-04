<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('role')->default('editor');
            $t->boolean('active')->default(true);
        });
        Schema::table('contents', function (Blueprint $t) {
            $t->unsignedInteger('version')->default(1);
        });
        Schema::create('cms_posts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('content_id')->unique()->constrained()->cascadeOnDelete();
            $t->jsonb('data');
            $t->timestampsTz();
        });
        Schema::create('cms_collections', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->jsonb('data');
            $t->unsignedInteger('version')->default(1);
            $t->timestampsTz();
        });
        Schema::create('cms_revisions', function (Blueprint $t) {
            $t->id();
            $t->string('entity');
            $t->string('entity_id');
            $t->foreignId('user_id')->nullable();
            $t->jsonb('snapshot');
            $t->string('description');
            $t->timestampsTz();
            $t->index(['entity', 'entity_id']);
        });
        Schema::create('cms_audit', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable();
            $t->string('action');
            $t->string('entity');
            $t->string('entity_id')->nullable();
            $t->jsonb('details')->nullable();
            $t->timestampTz('created_at');
        });
        Schema::create('cms_designs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('content_id')->constrained()->cascadeOnDelete();
            $t->string('locale', 5);
            $t->jsonb('data');
            $t->timestampsTz();
            $t->unique(['content_id', 'locale']);
        });
    }

    public function down(): void
    {
        foreach (['cms_designs', 'cms_audit', 'cms_revisions', 'cms_collections', 'cms_posts'] as $t) {
            Schema::dropIfExists($t);
        }Schema::table('contents', fn (Blueprint $t) => $t->dropColumn('version'));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['role', 'active']));
    }
};
