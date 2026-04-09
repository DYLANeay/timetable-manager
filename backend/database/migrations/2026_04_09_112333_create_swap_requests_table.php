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
        Schema::create('swap_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users');
            $table->foreignId('target_id')->constrained('users');
            $table->foreignId('requester_shift_id')->constrained('shifts');
            $table->foreignId('target_shift_id')->constrained('shifts');
            $table->string('status')->default('pending_peer');
            $table->timestamp('peer_responded_at')->nullable();
            $table->timestamp('manager_decided_at')->nullable();
            $table->foreignId('manager_id')->nullable()->constrained('users');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('swap_requests');
    }
};
