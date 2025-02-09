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
        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('title', 255);
            $table->text('description');
            $table->date('release_date');
            $table->decimal('imdb_rating', 3, 1);
            $table->integer('duration');
            $table->enum('type', ['movie', 'series', 'anime-movie', 'anime-series']);
            $table->unsignedBigInteger('total_views')->default(0);
            $table->string('country', 100);
            $table->foreignId('saga_id')->nullable()->constrained('sagas')->onDelete('set null');
            $table->string('poster_path')->nullable();
            $table->string('thumbnail_path')->nullable();
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
        Schema::dropIfExists('contents');
    }
};
