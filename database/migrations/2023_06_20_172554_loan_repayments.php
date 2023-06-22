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
        Schema::create('loan_repayments', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->uuid("user_id");
            $table->uuid("loan_id");
            $table->foreign("user_id")->references("id")->on("users");
            $table->foreign("loan_id")->references("id")->on("loan_requests");
            $table->boolean("payment_status")->default(config("constants.PAYMENT_STATUS.PENDING"));
            $table->decimal("paid_amount", 6, 2);
            $table->integer("term_count", false);
            $table->boolean("is_active")->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_repayments');
    }
};
