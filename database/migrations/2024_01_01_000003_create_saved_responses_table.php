<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('collection_id');
            $table->string('endpoint_id');
            $table->string('endpoint_name');
            $table->string('method', 10);
            $table->string('url', 2000);
            $table->integer('status_code');
            $table->longText('response_body')->nullable();
            $table->json('request_headers')->nullable();
            $table->text('request_body')->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->string('label')->nullable(); // user-given name for the saved response
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_responses');
    }
};
