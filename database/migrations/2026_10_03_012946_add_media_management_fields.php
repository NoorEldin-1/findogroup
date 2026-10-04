<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_assets', function (Blueprint $t) {
            $t->boolean('managed_alt')->default(false);
            $t->boolean('archived')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('media_assets', fn (Blueprint $t) => $t->dropColumn(['managed_alt', 'archived']));
    }
};
