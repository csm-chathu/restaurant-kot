<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE sales MODIFY COLUMN source ENUM('pos','customer','uber') DEFAULT 'pos'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE sales MODIFY COLUMN source ENUM('pos','customer') DEFAULT 'pos'");
    }
};
