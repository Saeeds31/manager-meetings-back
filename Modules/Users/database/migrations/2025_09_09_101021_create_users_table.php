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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('father_name');
            $table->date('birth_date');
            $table->text('place_of_residence');
            $table->string('national_code');
            $table->string('birth_certificate_number');
            $table->string('place_birth_certificate');
            $table->boolean('marital_status')->default(false);
            $table->string('job_address');
            $table->string('mobile')->unique();
            $table->string('phone')->nullable();
            $table->string("front_national_cart");
            $table->string("back_national_cart");
            $table->string("birth_certificate_image");
            $table->string("image");
            $table->enum("status", ["in_progress","pending", "rejected", "accepted"])->default("pending");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
