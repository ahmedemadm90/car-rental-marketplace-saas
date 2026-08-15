<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_groups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->ulid('uuid')->unique();
            $table->string('name');
            $table->string('code', 32);
            $table->string('category', 64);
            $table->string('make')->nullable();
            $table->string('model')->nullable();
            $table->unsignedTinyInteger('seats')->default(5);
            $table->unsignedTinyInteger('doors')->nullable();
            $table->string('transmission', 24);
            $table->string('fuel_type', 24);
            $table->boolean('air_conditioning')->default(true);
            $table->unsignedSmallInteger('luggage_capacity')->default(0);
            $table->json('features')->nullable();
            $table->json('media')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'is_public', 'is_active']);
            $table->index(['company_id', 'category', 'transmission', 'fuel_type']);
        });

        Schema::create('vehicles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('vehicle_group_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->ulid('uuid')->unique();
            $table->string('registration_number', 64);
            $table->string('vin', 64)->nullable();
            $table->unsignedSmallInteger('model_year')->nullable();
            $table->unsignedBigInteger('odometer_km')->default(0);
            $table->string('status', 24)->default('available');
            $table->string('color', 32)->nullable();
            $table->timestamp('last_inspected_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'registration_number']);
            $table->unique(['company_id', 'vin']);
            $table->index(['company_id', 'branch_id', 'vehicle_group_id', 'status']);
        });

        Schema::create('rate_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->ulid('uuid')->unique();
            $table->string('name');
            $table->string('code', 32);
            $table->char('currency', 3);
            $table->unsignedBigInteger('daily_rate_minor');
            $table->unsignedBigInteger('deposit_minor')->default(0);
            $table->unsignedBigInteger('included_km_per_day')->nullable();
            $table->unsignedBigInteger('extra_km_rate_minor')->default(0);
            $table->json('rules')->nullable();
            $table->json('fees')->nullable();
            $table->json('taxes')->nullable();
            $table->timestamp('active_from')->nullable();
            $table->timestamp('active_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'is_active', 'active_from', 'active_until']);
        });

        Schema::create('vehicle_group_rate_plan', function (Blueprint $table): void {
            $table->foreignId('vehicle_group_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('rate_plan_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->primary(['vehicle_group_id', 'rate_plan_id']);
        });

        Schema::create('reservations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('vehicle_group_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('pickup_branch_id')->constrained('branches')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('return_branch_id')->constrained('branches')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('rate_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->ulid('uuid')->unique();
            $table->string('reference', 24)->unique();
            $table->string('status', 32)->index();
            $table->timestamp('pickup_at');
            $table->timestamp('return_at');
            $table->timestamp('hold_expires_at')->nullable();
            $table->char('currency', 3);
            $table->unsignedBigInteger('subtotal_minor');
            $table->unsignedBigInteger('discount_minor')->default(0);
            $table->unsignedBigInteger('tax_minor')->default(0);
            $table->unsignedBigInteger('fee_minor')->default(0);
            $table->unsignedBigInteger('deposit_minor')->default(0);
            $table->unsignedBigInteger('total_minor');
            $table->unsignedBigInteger('cancellation_fee_minor')->default(0);
            $table->json('pricing_snapshot');
            $table->json('cancellation_policy_snapshot')->nullable();
            $table->json('customer_snapshot');
            $table->text('customer_notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['company_id', 'status', 'pickup_at']);
            $table->index(['company_id', 'vehicle_group_id', 'pickup_at', 'return_at']);
            $table->index(['customer_id', 'status', 'created_at']);
        });

        Schema::create('reservation_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('reservation_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_group_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('status', 24)->default('held');
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'vehicle_group_id', 'starts_at', 'ends_at', 'status']);
            $table->index(['company_id', 'vehicle_id', 'starts_at', 'ends_at', 'status']);
        });

        Schema::create('waitlist_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('vehicle_group_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('pickup_branch_id')->constrained('branches')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('return_branch_id')->constrained('branches')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamp('pickup_at');
            $table->timestamp('return_at');
            $table->char('currency', 3);
            $table->unsignedBigInteger('max_total_minor')->nullable();
            $table->string('status', 24)->default('waiting');
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->index(['company_id', 'vehicle_group_id', 'status', 'pickup_at', 'return_at']);
        });

        Schema::create('wallets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->char('currency', 3);
            $table->bigInteger('balance_minor')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'currency']);
        });

        Schema::create('wallet_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->ulid('uuid')->unique();
            $table->string('type', 32);
            $table->bigInteger('amount_minor');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('idempotency_key', 128)->unique();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->index(['wallet_id', 'occurred_at']);
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->ulid('uuid')->unique();
            $table->string('provider', 64);
            $table->string('provider_reference')->nullable();
            $table->string('type', 24);
            $table->string('status', 24);
            $table->char('currency', 3);
            $table->unsignedBigInteger('amount_minor');
            $table->string('idempotency_key', 128)->unique();
            $table->json('provider_payload')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'reservation_id', 'status']);
            $table->unique(['provider', 'provider_reference']);
        });

        Schema::create('maintenance_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->ulid('uuid')->unique();
            $table->string('type', 32);
            $table->string('status', 24);
            $table->string('vendor_name')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->unsignedBigInteger('cost_minor')->default(0);
            $table->char('currency', 3);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'vehicle_id', 'status', 'starts_at', 'ends_at']);
        });

        Schema::create('inspections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('reservation_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('performed_by_user_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->ulid('uuid')->unique();
            $table->string('type', 24);
            $table->unsignedBigInteger('odometer_km');
            $table->string('fuel_level', 24);
            $table->json('checklist');
            $table->json('media')->nullable();
            $table->json('geo')->nullable();
            $table->timestamp('performed_at');
            $table->timestamps();
            $table->index(['company_id', 'reservation_id', 'type']);
        });

        Schema::create('damage_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('reported_by_user_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->ulid('uuid')->unique();
            $table->string('status', 24)->default('reported');
            $table->string('severity', 24);
            $table->text('description');
            $table->unsignedBigInteger('estimated_cost_minor')->default(0);
            $table->char('currency', 3);
            $table->json('media')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'vehicle_id', 'status']);
        });

        Schema::create('customer_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->ulid('uuid')->unique();
            $table->string('type', 32);
            $table->string('status', 24)->default('pending');
            $table->string('disk', 32);
            $table->string('path');
            $table->string('sha256', 64);
            $table->date('expires_on')->nullable();
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->unique(['disk', 'path']);
            $table->index(['user_id', 'type', 'status']);
        });

        Schema::create('contracts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('reservation_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->ulid('uuid')->unique();
            $table->string('template_version', 64);
            $table->string('status', 24)->default('draft');
            $table->string('disk', 32);
            $table->string('path');
            $table->string('document_hash', 64);
            $table->string('signature_hash', 64)->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->string('signed_ip_address', 45)->nullable();
            $table->text('signed_user_agent')->nullable();
            $table->timestamps();
            $table->unique(['disk', 'path']);
            $table->index(['company_id', 'reservation_id', 'status']);
        });

        Schema::create('reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('reservation_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->string('status', 24)->default('pending');
            $table->timestamps();
            $table->unique(['reservation_id', 'customer_id']);
            $table->index(['company_id', 'status', 'rating']);
        });

        Schema::create('support_tickets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('requester_user_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->ulid('uuid')->unique();
            $table->string('reference', 24)->unique();
            $table->string('subject');
            $table->string('status', 24)->default('open');
            $table->string('priority', 24)->default('normal');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'status', 'priority']);
        });

        Schema::create('support_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('sender_user_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->text('body');
            $table->json('attachments')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['support_ticket_id', 'created_at']);
        });

        Schema::create('notification_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->ulid('uuid')->unique();
            $table->string('channel', 24);
            $table->string('template', 128);
            $table->string('status', 24)->default('queued');
            $table->string('idempotency_key', 128)->unique();
            $table->json('payload');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'channel', 'status']);
        });

        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('number', 64);
            $table->string('status', 24);
            $table->char('currency', 3);
            $table->unsignedBigInteger('subtotal_minor');
            $table->unsignedBigInteger('tax_minor')->default(0);
            $table->unsignedBigInteger('total_minor');
            $table->json('lines');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'number']);
            $table->index(['company_id', 'status', 'issued_at']);
        });

        Schema::create('expenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by_user_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->ulid('uuid')->unique();
            $table->string('category', 64);
            $table->char('currency', 3);
            $table->unsignedBigInteger('amount_minor');
            $table->date('incurred_on');
            $table->text('description')->nullable();
            $table->string('receipt_path')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'category', 'incurred_on']);
        });
    }

    public function down(): void
    {
        foreach ([
            'expenses', 'invoices', 'notification_deliveries', 'support_messages', 'support_tickets', 'reviews', 'contracts',
            'customer_documents', 'damage_reports', 'inspections', 'maintenance_records', 'payments', 'wallet_entries', 'wallets',
            'waitlist_entries', 'reservation_allocations', 'reservations', 'vehicle_group_rate_plan', 'rate_plans', 'vehicles', 'vehicle_groups',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
