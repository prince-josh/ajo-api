<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contribution_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ajo_group_id')->constrained()->onDelete('cascade');
            $table->foreignId('recipient_id')->constrained('users')->onDelete('cascade'); // who gets paid this cycle
            $table->integer('cycle_number'); // cycle 1, 2, 3...
            $table->decimal('expected_total', 15, 2); // contribution_amount * number of members
            $table->decimal('amount_collected', 15, 2)->default(0.00);
            $table->enum('status', ['pending', 'active', 'completed'])->default('pending');
            $table->date('due_date');
            $table->timestamp('paid_out_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contribution_cycles');
    }
};
