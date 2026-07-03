<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->enum('order_type', ['dine_in', 'takeaway'])->default('dine_in')->after('table_number');
            $table->decimal('service_charge', 12, 2)->default(0)->after('order_type');
            $table->decimal('service_charge_rate', 5, 2)->default(0)->after('service_charge');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['order_type', 'service_charge', 'service_charge_rate']);
        });
    }
};
