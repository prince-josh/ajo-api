<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ajo_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 10)->unique(); // unique group join code
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->decimal('contribution_amount', 15, 2); // how much each member contributes per cycle
            $table->enum('frequency', ['daily', 'weekly', 'biweekly', 'monthly']);
            $table->integer('max_members');
            $table->enum('status', ['pending', 'active', 'completed', 'suspended'])->default('pending');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ajo_groups');
    }
};
