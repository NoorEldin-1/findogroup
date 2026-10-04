<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operating_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('type', 40)->index();
            $table->string('title', 500);
            $table->string('status', 40)->index();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('data');
            $table->unsignedInteger('version')->default(1);
            $table->string('source', 20)->default('findo');
            $table->string('external_id')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->unique(['source', 'external_id']);
            $table->index(['type', 'status', 'updated_at']);
        });
        Schema::create('operating_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->constrained('operating_records')->cascadeOnDelete();
            $table->foreignId('target_id')->constrained('operating_records')->restrictOnDelete();
            $table->string('field', 60);
            $table->unique(['record_id', 'field']);
            $table->index(['target_id', 'field']);
        });
        Schema::create('operating_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->nullable()->constrained('operating_records')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 40);
            $table->string('reason', 2000);
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
        Schema::create('operating_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->constrained('operating_records')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->text('body');
            $table->timestamp('created_at')->useCurrent();
        });
        Schema::create('operating_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->constrained('operating_records')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('name', 250);
            $table->string('path', 500);
            $table->string('mime', 150);
            $table->unsignedBigInteger('bytes');
            $table->timestamp('created_at')->useCurrent();
        });
        Schema::create('operating_integrations', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('status')->default('disconnected');
            $table->json('settings');
            $table->text('secret')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->string('last_error', 500)->nullable();
            $table->timestamps();
        });
        Schema::create('operating_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->string('integration', 40);
            $table->string('status', 30);
            $table->unsignedInteger('attempts')->default(1);
            $table->unsignedInteger('records')->default(0);
            $table->string('error', 500)->nullable();
            $table->json('payload')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['operating_sync_runs', 'operating_integrations', 'operating_attachments', 'operating_comments', 'operating_events', 'operating_links', 'operating_records'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
