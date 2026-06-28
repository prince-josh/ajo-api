<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contribution_cycle_id')->constrained()->onDelete('cascade');
            $table->foreignId('ajo_group_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // who made the contribution
            $table->foreignId('transaction_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('amount', 15, 2);
            $table->enum('status', ['pending', 'paid', 'defaulted'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['contribution_cycle_id', 'user_id']); // one contribution per user per cycle
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contributions');
    }
};
