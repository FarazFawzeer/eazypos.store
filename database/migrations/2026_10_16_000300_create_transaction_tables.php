<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registers, sales, purchases, returns, expenses, stock history,
     * quotations and shipments.
     */
    public function up(): void
    {
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('opened_by')->nullable();
            $table->uuid('closed_by')->nullable();
            $table->dateTime('opened_at', 6)->useCurrent();
            $table->dateTime('closed_at', 6)->nullable();
            $table->decimal('opening_cash', 14, 2)->default(0);
            $table->decimal('cash_sales', 14, 2)->default(0);
            $table->decimal('cash_refunds', 14, 2)->default(0);
            $table->decimal('cash_expenses', 14, 2)->default(0);
            $table->decimal('cash_inflows', 14, 2)->default(0);
            $table->decimal('cash_outflows', 14, 2)->default(0);
            $table->decimal('cash_adjustments', 14, 2)->default(0);
            $table->decimal('expected_closing_cash', 14, 2)->default(0);
            $table->decimal('actual_closing_cash', 14, 2)->nullable();
            $table->decimal('variance', 14, 2)->nullable();
            $table->string('status', 20)->default('open'); // open | closed
            $table->text('notes')->nullable();
            $table->dateTime('created_at', 6)->useCurrent();

            $table->index(['business_id', 'status']);
            $table->index('opened_at');
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('opened_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('closed_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('register_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('register_id');
            $table->string('transaction_type', 20); // cash_sale | cash_refund | expense | cash_in | cash_out | adjustment
            $table->decimal('amount', 14, 2);
            $table->uuid('reference_id')->nullable();
            $table->text('description')->nullable();
            $table->uuid('created_by')->nullable();
            $table->dateTime('created_at', 6)->useCurrent();

            $table->index('business_id');
            $table->index('register_id');
            $table->index('reference_id');
            $table->index('created_at');
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('register_id')->references('id')->on('cash_registers')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('customer_id')->nullable();
            $table->string('invoice_number', 40);
            $table->dateTime('sale_date', 6)->useCurrent();
            $table->string('payment_status', 20)->default('paid'); // paid | partial | credit | refunded | partially_refunded
            $this->amounts($table, ['subtotal', 'discount', 'tax', 'shipping_charge', 'other_charge', 'grand_total', 'amount_paid', 'amount_due', 'change_amount']);
            $table->string('status', 20)->default('completed'); // draft | completed | cancelled
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->dateTime('created_at', 6)->useCurrent();
            $table->dateTime('updated_at', 6)->useCurrent();

            $table->unique(['business_id', 'invoice_number']);
            $table->index('customer_id');
            $table->index('sale_date');
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sale_id');
            $table->uuid('business_id');
            $table->uuid('product_id');
            $table->decimal('quantity', 14, 3);
            $this->amounts($table, ['unit_price', 'cost_price', 'discount', 'tax', 'line_total']);
            $table->dateTime('created_at', 6)->useCurrent();

            $table->index('sale_id');
            $table->index('product_id');
            $table->foreign('sale_id')->references('id')->on('sales')->cascadeOnDelete();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
        });

        Schema::create('sale_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sale_id');
            $table->uuid('business_id');
            $table->string('payment_method', 20); // cash | card | bank_transfer | credit | advance | cheque | other | cod
            $table->decimal('amount', 14, 2);
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('created_at', 6)->useCurrent();

            $table->index('sale_id');
            $table->index('payment_method');
            $table->foreign('sale_id')->references('id')->on('sales')->cascadeOnDelete();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('sale_id')->nullable();
            $table->string('invoice_number', 40);
            $table->dateTime('invoice_date', 6)->useCurrent();
            $table->uuid('customer_id')->nullable();
            $table->string('status', 20)->default('issued'); // draft | issued | paid | partially_paid | cancelled
            $this->amounts($table, ['subtotal', 'discount', 'tax', 'shipping_charge', 'other_charge', 'grand_total', 'amount_paid', 'amount_due']);
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->dateTime('created_at', 6)->useCurrent();

            $table->unique(['business_id', 'invoice_number']);
            $table->index('sale_id');
            $table->index('customer_id');
            $table->index('invoice_date');
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('sale_id')->references('id')->on('sales')->nullOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('sale_returns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('sale_id')->nullable();
            $table->uuid('customer_id')->nullable();
            $table->string('return_number', 40);
            $table->dateTime('return_date', 6)->useCurrent();
            $this->amounts($table, ['subtotal', 'discount', 'tax', 'grand_total']);
            $table->string('refund_method', 20)->default('cash');
            $table->string('status', 20)->default('completed');
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->dateTime('created_at', 6)->useCurrent();

            $table->unique(['business_id', 'return_number']);
            $table->index('sale_id');
            $table->index('customer_id');
            $table->index('return_date');
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('sale_id')->references('id')->on('sales')->nullOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('sale_return_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sale_return_id');
            $table->uuid('business_id');
            $table->uuid('product_id');
            $table->decimal('quantity', 14, 3);
            $this->amounts($table, ['unit_price', 'discount', 'tax', 'line_total']);
            $table->dateTime('created_at', 6)->useCurrent();

            $table->index('sale_return_id');
            $table->index('product_id');
            $table->foreign('sale_return_id')->references('id')->on('sale_returns')->cascadeOnDelete();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('supplier_id')->nullable();
            $table->string('reference_number', 40);
            $table->dateTime('purchase_date', 6)->useCurrent();
            $table->string('payment_method', 20)->default('cash');
            $this->amounts($table, ['subtotal', 'discount', 'tax', 'shipping_charge', 'other_charge', 'grand_total', 'amount_paid', 'amount_due']);
            $table->string('status', 20)->default('completed');
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->dateTime('created_at', 6)->useCurrent();
            $table->dateTime('updated_at', 6)->useCurrent();

            $table->unique(['business_id', 'reference_number']);
            $table->index('supplier_id');
            $table->index('purchase_date');
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('purchase_id');
            $table->uuid('business_id');
            $table->uuid('product_id');
            $table->decimal('quantity', 14, 3);
            $this->amounts($table, ['unit_cost', 'selling_price', 'discount', 'tax', 'line_total']);
            $table->dateTime('created_at', 6)->useCurrent();

            $table->index('purchase_id');
            $table->index('product_id');
            $table->foreign('purchase_id')->references('id')->on('purchases')->cascadeOnDelete();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
        });

        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('supplier_id')->nullable();
            $table->uuid('purchase_id')->nullable();
            $table->string('reference_number', 40);
            $table->dateTime('return_date', 6)->useCurrent();
            $this->amounts($table, ['subtotal', 'discount', 'tax', 'other_charge', 'grand_total']);
            $table->string('refund_method', 20)->default('credit');
            $table->string('status', 20)->default('completed');
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->dateTime('created_at', 6)->useCurrent();

            $table->unique(['business_id', 'reference_number']);
            $table->index('supplier_id');
            $table->index('return_date');
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->nullOnDelete();
            $table->foreign('purchase_id')->references('id')->on('purchases')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('purchase_return_id');
            $table->uuid('business_id');
            $table->uuid('product_id');
            $table->decimal('quantity', 14, 3);
            $this->amounts($table, ['unit_cost', 'discount', 'tax', 'line_total']);
            $table->dateTime('created_at', 6)->useCurrent();

            $table->index('purchase_return_id');
            $table->index('product_id');
            $table->foreign('purchase_return_id')->references('id')->on('purchase_returns')->cascadeOnDelete();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
        });

        Schema::create('expense_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->dateTime('created_at', 6)->useCurrent();

            $table->unique(['business_id', 'name']);
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('category_id')->nullable();
            $table->text('description');
            $table->decimal('amount', 14, 2);
            $table->string('payment_method', 20)->default('cash');
            $table->dateTime('expense_date', 6)->useCurrent();
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->dateTime('created_at', 6)->useCurrent();
            $table->dateTime('updated_at', 6)->useCurrent();

            $table->index('business_id');
            $table->index('category_id');
            $table->index('expense_date');
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('category_id')->references('id')->on('expense_categories')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('product_id');
            $table->string('movement_type', 30);
            $table->decimal('quantity', 14, 3);
            $table->uuid('reference_id')->nullable();
            $table->text('note')->nullable();
            $table->uuid('created_by')->nullable();
            $table->dateTime('created_at', 6)->useCurrent();

            $table->index('business_id');
            $table->index('product_id');
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('quotations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('customer_id')->nullable();
            $table->string('quotation_number', 40);
            $table->dateTime('quotation_date', 6)->useCurrent();
            $table->dateTime('valid_until', 6)->nullable();
            $this->amounts($table, ['subtotal', 'discount', 'tax', 'shipping_charge', 'other_charge', 'grand_total']);
            $table->string('status', 20)->default('draft');
            $table->uuid('converted_sale_id')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->dateTime('created_at', 6)->useCurrent();
            $table->dateTime('updated_at', 6)->useCurrent();

            $table->unique(['business_id', 'quotation_number']);
            $table->index('customer_id');
            $table->index('quotation_date');
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->foreign('converted_sale_id')->references('id')->on('sales')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('quotation_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('quotation_id');
            $table->uuid('business_id');
            $table->uuid('product_id')->nullable();
            $table->string('product_name');
            $table->decimal('quantity', 14, 3);
            $this->amounts($table, ['unit_price', 'discount', 'tax', 'line_total']);
            $table->dateTime('created_at', 6)->useCurrent();

            $table->index('quotation_id');
            $table->index('product_id');
            $table->foreign('quotation_id')->references('id')->on('quotations')->cascadeOnDelete();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('sale_id')->nullable();
            $table->uuid('customer_id')->nullable();
            $table->string('shipment_number', 60);
            $table->dateTime('shipping_date', 6)->nullable();
            $table->dateTime('expected_delivery_date', 6)->nullable();
            $table->dateTime('delivered_at', 6)->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('carrier')->nullable();
            $table->string('tracking_number')->nullable();
            $table->decimal('shipping_cost', 14, 2)->default(0);
            $table->string('status', 20)->default('pending');
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->dateTime('created_at', 6)->useCurrent();
            $table->dateTime('updated_at', 6)->useCurrent();

            $table->unique(['business_id', 'shipment_number']);
            $table->index('sale_id');
            $table->index('customer_id');
            $table->index('shipping_date');
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('sale_id')->references('id')->on('sales')->nullOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Add DECIMAL(14,2) money columns that default to zero.
     *
     * @param  list<string>  $columns
     */
    private function amounts(Blueprint $table, array $columns): void
    {
        foreach ($columns as $column) {
            $table->decimal($column, 14, 2)->default(0);
        }
    }

    public function down(): void
    {
        foreach ([
            'shipments', 'quotation_items', 'quotations', 'stock_movements', 'expenses', 'expense_categories',
            'purchase_return_items', 'purchase_returns', 'purchase_items', 'purchases', 'sale_return_items',
            'sale_returns', 'invoices', 'sale_payments', 'sale_items', 'sales', 'register_transactions', 'cash_registers',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
