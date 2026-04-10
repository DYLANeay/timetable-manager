<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('swap_requests', function (Blueprint $table) {
            $table->foreignId('target_id')->nullable()->change();
            $table->foreignId('target_shift_id')->nullable()->change();
            $table->string('type')->default('swap')->after('target_shift_id');
        });
    }

    public function down(): void
    {
        Schema::table('swap_requests', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->foreignId('target_id')->nullable(false)->change();
            $table->foreignId('target_shift_id')->nullable(false)->change();
        });
    }
};
