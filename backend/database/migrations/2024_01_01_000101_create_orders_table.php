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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('symbol', 10)->index();
            $table->string('side', 10);
            $table->decimal('price', 18, 2);
            $table->decimal('amount', 24, 12);
            $table->tinyInteger('status')->default(1)->index();
            $table->decimal('reserved_value', 18, 2)->default(0);
            $table->timestamp('filled_at')->nullable();
            $table->timestamps();

            $table->index(['symbol', 'side', 'status', 'price']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
