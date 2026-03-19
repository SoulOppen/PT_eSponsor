<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->unique(); // one site per user
            $table->string('name', 100);
            $table->string('slug', 60)->unique();
            $table->text('bio')->nullable();
            $table->string('avatar_url', 500)->nullable();
            $table->timestamp('published_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};

