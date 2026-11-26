<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goali_trips', function (Blueprint $table) {
            $table->decimal('company_profit', 10, 2)->nullable()->after('amount');
            $table->char('duplicate_hash', 64)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('goali_trips', function (Blueprint $table) {
            $table->dropUnique(['duplicate_hash']);
            $table->dropColumn(['company_profit', 'duplicate_hash']);
        });
    }
};
