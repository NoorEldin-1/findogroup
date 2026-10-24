<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Who a lead is shared with (read access) and who follows it.
     */
    public function up(): void
    {
        Schema::create('crm_lead_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('operating_records')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('relation', 20);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['lead_id', 'user_id', 'relation']);
            $table->index(['user_id', 'relation']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_lead_relations');
    }
};
