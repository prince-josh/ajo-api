<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ajo_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ajo_group_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('slot_number'); // the order/position of payout (1, 2, 3 ...)
            $table->enum('status', ['active', 'suspended', 'exited'])->default('active');
            $table->boolean('has_collected')->default(false);
            $table->timestamp('collected_at')->nullable();
            $table->boolean('is_admin')->default(false); // group admin (creator)
            $table->timestamps();

            $table->unique(['ajo_group_id', 'user_id']);
            $table->unique(['ajo_group_id', 'slot_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ajo_group_members');
    }
};
