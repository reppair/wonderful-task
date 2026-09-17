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
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('clinic_name');
            $table->string('location')->index();
            $table->string('speciality')->index();
            $table->string('address');
            $table->string('phone');
            $table->string('email');
            $table->string('postal_code');
            $table->string('county')->index();
            $table->unsignedInteger('years_experience');
            $table->string('education');
            $table->json('languages');
            $table->string('availability');
            $table->decimal('rating', 3, 1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
