<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Businesses, staff membership, invitations and document numbering.
     */
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('business_type')->nullable();
            $table->string('country')->nullable();
            $table->string('currency', 10)->default('LKR');
            $table->text('logo_url')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->text('address')->nullable();
            $table->string('status', 20)->default('active'); // active | suspended | closed
            $table->uuid('owner_id')->nullable();
            $table->dateTime('created_at', 6)->useCurrent();
            $table->dateTime('updated_at', 6)->useCurrent();

            $table->foreign('owner_id')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::create('business_users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('user_id');
            $table->string('role', 20)->default('cashier'); // owner | admin | manager | cashier
            $table->json('permissions');
            $table->string('status', 20)->default('active'); // active | inactive
            $table->string('full_name', 120)->nullable();
            $table->string('username', 32)->nullable();
            $table->dateTime('created_at', 6)->useCurrent();

            $table->unique(['business_id', 'user_id']);
            $table->unique(['business_id', 'username']);
            $table->index('user_id');
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('staff_invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('email');
            $table->string('full_name', 120)->nullable();
            $table->string('username', 32)->nullable();
            $table->string('role', 20)->default('cashier'); // admin | manager | cashier
            $table->json('permissions');
            $table->uuid('invited_by');
            $table->string('status', 20)->default('pending'); // pending | accepted | cancelled | expired
            $table->string('token_hash', 64)->nullable()->unique();
            $table->dateTime('expires_at', 6);
            $table->dateTime('accepted_at', 6)->nullable();
            $table->dateTime('created_at', 6)->useCurrent();

            $table->index('business_id');
            $table->index('email');
            $table->index('status');
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('invited_by')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('document_sequences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('document_type', 30); // invoice | quotation | purchase | purchase_return | sale_return | shipment
            $table->string('prefix', 10);
            $table->unsignedBigInteger('next_number')->default(1);
            $table->dateTime('created_at', 6)->useCurrent();
            $table->dateTime('updated_at', 6)->useCurrent();

            $table->unique(['business_id', 'document_type']);
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        Schema::create('inquiries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email');
            $table->string('business')->nullable();
            $table->string('topic', 80)->nullable();
            $table->text('message');
            $table->string('status', 20)->default('new'); // new | contacted | closed
            $table->string('ip_address', 45)->nullable();
            $table->dateTime('created_at', 6)->useCurrent();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiries');
        Schema::dropIfExists('document_sequences');
        Schema::dropIfExists('staff_invitations');
        Schema::dropIfExists('business_users');
        Schema::dropIfExists('businesses');
    }
};
