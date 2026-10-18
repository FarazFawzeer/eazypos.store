<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Quotations keep the free-text customer typed on the Quotations screen,
     * and businesses keep the tax % and receipt footer from Settings.
     */
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->string('customer_name')->nullable()->after('customer_id');
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->decimal('tax_rate', 5, 2)->default(0)->after('currency');
            $table->string('receipt_footer')->nullable()->after('tax_rate');
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn('customer_name');
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['tax_rate', 'receipt_footer']);
        });
    }
};
