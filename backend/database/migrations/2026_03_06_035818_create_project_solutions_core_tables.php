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
    // Menu 1: Sales (Clients/Leads)
    Schema::create('sales_leads', function ($table) {
        $table->id();
        $table->string('client_name');
        $table->string('company_name');
        $table->string('contact_number')->nullable();
        $table->string('status')->default('PROSPECT'); // PROSPECT, NEGOTIATION, WON
        $table->timestamps();
    });

    // Menu 2: Project
    Schema::create('projects', function ($table) {
        $table->id();
        $table->foreignId('lead_id')->constrained('sales_leads')->onDelete('cascade');
        $table->string('project_title');
        $table->integer('progress_percent')->default(0);
        $table->decimal('contract_value', 15, 2);
        $table->date('deadline');
        $table->timestamps();
    });

    // Menu 3: Produks (Products)
    Schema::create('products_catalog', function ($table) {
        $table->id();
        $table->string('item_name');
        $table->text('specs')->nullable();
        $table->decimal('base_price', 15, 2);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_solutions_core_tables');
    }
};
