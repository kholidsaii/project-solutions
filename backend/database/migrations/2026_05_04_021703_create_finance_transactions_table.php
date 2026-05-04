<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('finance_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number')->unique(); // Generate otomatis: TRX-2026...
            $table->date('transaction_date');
            $table->string('ref_number')->nullable();
            $table->enum('type', ['inflow', 'outflow']); // Uang masuk / keluar
            $table->foreignId('project_id')->nullable()->constrained('projects')->onDelete('set null');
            $table->foreignId('coa_id')->nullable()->constrained('accounting_coas')->onDelete('set null');
            
            $table->string('method'); // transfer, cash, ewallet
            $table->string('bank_from')->nullable();
            $table->string('bank_to')->nullable();
            $table->decimal('amount', 15, 2);
            
            $table->text('description')->nullable();
            $table->string('label_id')->nullable(); // Untuk taging (urgent, vendor, dll)
            $table->string('attachment_path')->nullable(); // Path file bukti transfer/invoice
            
            $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_transactions');
    }
};
