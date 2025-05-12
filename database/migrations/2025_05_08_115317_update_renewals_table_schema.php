<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('renewals', function (Blueprint $table) {
            // Rename columns to match the new schema
            $table->renameColumn('service_name', 'item_name');
            $table->renameColumn('service_type', 'category');
            $table->renameColumn('provider', 'vendor');
            $table->renameColumn('reminder_type', 'reminder_days_before');

            // Change the status enum values
            $table->dropColumn('status');
            $table->enum('status', ['active', 'renewed', 'inactive', 'cancelled'])->default('active');
            
            // Make vendor nullable
            $table->string('vendor')->nullable()->change();
            
            // Convert reminder_days_before from string to integer
            $table->integer('reminder_days_before')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('renewals', function (Blueprint $table) {
            $table->renameColumn('item_name', 'service_name');
            $table->renameColumn('category', 'service_type');
            $table->renameColumn('vendor', 'provider');
            $table->renameColumn('reminder_days_before', 'reminder_type');
            
            $table->dropColumn('status');
            $table->enum('status', ['active', 'expiring-soon', 'expired'])->default('active');
            
            $table->string('vendor')->nullable(false)->change();
            $table->string('reminder_days_before')->change();
        });
    }
};
