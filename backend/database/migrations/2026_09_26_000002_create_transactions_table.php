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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // BR-07: a category in use cannot be deleted.
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->enum('type', ['income', 'expense']);
            $table->decimal('amount', 12, 2); // BR-01: always positive
            $table->date('transaction_date');
            $table->string('description', 255)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'transaction_date'], 'trx_user_date_idx');
            $table->index(['user_id', 'type', 'transaction_date'], 'trx_user_type_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
