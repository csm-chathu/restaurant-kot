<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->string('title');
            $table->string('message')->nullable();
            $table->string('invoice_number')->nullable();
            $table->string('table_number')->nullable();
            $table->decimal('total', 10, 2)->default(0);
            $table->unsignedTinyInteger('items_count')->default(0);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'read_at']);
        });

        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('endpoint', 500);
            $table->text('public_key')->nullable();
            $table->text('auth_token')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'endpoint'], 'push_sub_user_endpoint_unique');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
        Schema::dropIfExists('order_notifications');
    }
};
