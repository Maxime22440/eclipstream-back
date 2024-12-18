<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('episodes', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->uuid('season_id');
            $table->foreign('season_id')->references('uuid')->on('seasons')->onDelete('cascade');
            $table->integer('episode_number')->unsigned();
            $table->string('title', 255);
            $table->text('description');
            $table->date('release_date');
            $table->decimal('imdb_rating', 3, 1);
            $table->integer('duration');
            $table->string('video_link')->nullable();
            $table->string('stream_link')->nullable();
            $table->boolean('is_uploaded')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};
