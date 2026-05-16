<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('collection_id')->nullable()->index(); // null = global channel
            $table->enum('channel', ['general', 'collection', 'endpoint'])->default('general');
            $table->string('channel_key')->nullable(); // e.g. collectionId or endpointId
            $table->text('message');
            $table->enum('type', ['text', 'system', 'ai_mention'])->default('text');
            $table->json('mentions')->nullable(); // array of user_ids mentioned
            $table->boolean('email_sent')->default(false);
            $table->timestamps();

            $table->index(['channel', 'channel_key', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
