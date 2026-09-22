<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name_ar', 255);
            $table->string('name_en', 255);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('inventory_units', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name_ar', 100);
            $table->string('name_en', 100);
            $table->decimal('conversion_to_base', 12, 4)->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('inventory_categories');
            $table->string('sku', 80)->unique();
            $table->string('name_ar', 255);
            $table->string('name_en', 255);
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->foreignId('base_unit_id')->constrained('inventory_units');
            $table->string('barcode', 120)->nullable()->unique();
            $table->string('qr_token', 128)->unique();
            $table->decimal('reorder_level', 14, 3)->default(0);
            $table->decimal('minimum_stock', 14, 3)->default(0);
            $table->decimal('maximum_stock', 14, 3)->nullable();
            $table->decimal('safety_stock', 14, 3)->default(0);
            $table->boolean('is_transferable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['category_id', 'is_active']);
        });

        Schema::create('inventory_warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions');
            $table->string('code', 80)->unique();
            $table->string('name_ar', 255);
            $table->string('name_en', 255);
            $table->string('warehouse_type', 32)->default('central');
            $table->string('status', 24)->default('active');
            $table->string('address_ar')->nullable();
            $table->string('address_en')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
            $table->index(['institution_id', 'status']);
        });

        Schema::create('inventory_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('inventory_warehouses');
            $table->foreignId('item_id')->constrained('inventory_items');
            $table->decimal('quantity', 14, 3)->default(0);
            $table->decimal('reserved_quantity', 14, 3)->default(0);
            $table->decimal('average_unit_cost', 14, 4)->default(0);
            $table->timestamp('last_counted_at')->nullable();
            $table->timestamps();
            $table->unique(['warehouse_id', 'item_id']);
            $table->index(['item_id', 'quantity']);
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->uuid('movement_number')->unique();
            $table->foreignId('item_id')->constrained('inventory_items');
            $table->foreignId('warehouse_id')->constrained('inventory_warehouses');
            $table->foreignId('counterparty_warehouse_id')->nullable()->constrained('inventory_warehouses');
            $table->string('type', 32); // receipt, issue, transfer_out, transfer_in, adjustment, count
            $table->string('status', 32)->default('posted');
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_cost', 14, 4)->default(0);
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('reason_ar')->nullable();
            $table->text('reason_en')->nullable();
            $table->string('actor_type', 32)->nullable();
            $table->unsignedBigInteger('actor_account_id')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->index(['warehouse_id', 'item_id', 'type']);
            $table->index(['actor_type', 'actor_account_id']);
        });

        Schema::create('inventory_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('request_number')->unique();
            $table->foreignId('requesting_institution_id')->constrained('institutions');
            $table->foreignId('source_warehouse_id')->nullable()->constrained('inventory_warehouses');
            $table->string('type', 32)->default('issue'); // issue, transfer, replenishment
            $table->string('status', 40)->default('draft');
            $table->decimal('estimated_value', 14, 2)->default(0);
            $table->string('priority', 24)->default('normal');
            $table->text('reason_ar')->nullable();
            $table->text('reason_en')->nullable();
            $table->unsignedBigInteger('created_by_account_id');
            $table->unsignedBigInteger('responsible_account_id')->nullable();
            $table->timestamps();
            $table->index(['status', 'priority']);
        });

        Schema::create('inventory_request_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_request_id')->constrained('inventory_requests')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items');
            $table->decimal('requested_quantity', 14, 3);
            $table->decimal('approved_quantity', 14, 3)->nullable();
            $table->decimal('unit_cost', 14, 4)->default(0);
            $table->timestamps();
            $table->unique(['inventory_request_id', 'item_id']);
        });

        Schema::create('inventory_approval_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_request_id')->constrained('inventory_requests')->cascadeOnDelete();
            $table->unsignedSmallInteger('step_order');
            $table->string('approver_role', 80);
            $table->string('status', 24)->default('pending');
            $table->unsignedBigInteger('approver_account_id')->nullable();
            $table->text('comment')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();
            $table->unique(['inventory_request_id', 'step_order']);
            $table->index(['approver_role', 'status']);
        });

        Schema::create('inventory_reallocation_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('inventory_items');
            $table->foreignId('from_warehouse_id')->constrained('inventory_warehouses');
            $table->foreignId('to_warehouse_id')->constrained('inventory_warehouses');
            $table->decimal('suggested_quantity', 14, 3);
            $table->decimal('from_surplus', 14, 3);
            $table->decimal('to_shortage', 14, 3);
            $table->decimal('distance_km', 10, 2)->nullable();
            $table->decimal('priority_score', 12, 4)->default(0);
            $table->string('status', 24)->default('proposed');
            $table->text('reason_ar')->nullable();
            $table->text('reason_en')->nullable();
            $table->unsignedBigInteger('created_by_account_id')->nullable();
            $table->timestamps();
            $table->index(['item_id', 'status']);
        });

        Schema::create('inventory_counts', function (Blueprint $table) {
            $table->id();
            $table->uuid('count_number')->unique();
            $table->foreignId('warehouse_id')->constrained('inventory_warehouses');
            $table->string('status', 24)->default('open');
            $table->unsignedBigInteger('started_by_account_id');
            $table->unsignedBigInteger('closed_by_account_id')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_count_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_count_id')->constrained('inventory_counts')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items');
            $table->decimal('system_quantity', 14, 3);
            $table->decimal('counted_quantity', 14, 3)->nullable();
            $table->text('variance_reason_ar')->nullable();
            $table->text('variance_reason_en')->nullable();
            $table->unsignedBigInteger('counted_by_account_id')->nullable();
            $table->timestamp('counted_at')->nullable();
            $table->timestamps();
            $table->unique(['inventory_count_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_count_lines');
        Schema::dropIfExists('inventory_counts');
        Schema::dropIfExists('inventory_reallocation_suggestions');
        Schema::dropIfExists('inventory_approval_steps');
        Schema::dropIfExists('inventory_request_lines');
        Schema::dropIfExists('inventory_requests');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory_stock');
        Schema::dropIfExists('inventory_warehouses');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('inventory_units');
        Schema::dropIfExists('inventory_categories');
    }
};
