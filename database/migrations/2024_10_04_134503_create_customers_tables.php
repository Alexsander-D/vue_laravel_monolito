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
        // Tabela customers
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('type_person')->unique();
            $table->string('company_name');
            $table->string('trade_name')->nullable();
            $table->string('cep');
            $table->string('state');
            $table->string('city');
            $table->string('road');
            $table->string('district');
            $table->integer('number');
            $table->string('telephone');
            $table->string('email');
            $table->string('responsible');
            $table->text('observation')->nullable();
            $table->string('government')->default('varejo');
            $table->timestamps();
        });

        // Tabela screening
        Schema::create('screening', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customers_id')->constrained('customers')->onDelete('cascade');
            $table->string('type_person');
            $table->string('company_name');
            $table->string('trade_name')->nullable();
            $table->string('cep');
            $table->string('state');
            $table->string('city');
            $table->string('road');
            $table->string('district');
            $table->integer('number');
            $table->string('telephone');
            $table->string('email');
            $table->dateTime('scheduling_date')->nullable();
            $table->dateTime('service_start')->nullable();
            $table->dateTime('completion_date')->nullable();
            $table->dateTime('approval_date')->nullable();
            $table->string('rm')->nullable();
            $table->string('recovered_value')->nullable();
            $table->string('return_value')->nullable();
            $table->string('ndoa_value')->nullable();
            $table->string('type_service');
            $table->string('reject_report')->nullable();
            $table->string('air_ticket')->nullable();
            $table->string('nf')->nullable();
            $table->text('observation')->nullable();
            $table->string('status')->default('aguardando produtos');

            $table->timestamps();
        });
        // Tabela screening_report
        Schema::create('screening_report', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('screening_id')->constrained('screening')->onDelete('cascade');
            $table->foreignId('products_id')->constrained('products')->onDelete('cascade');
            $table->string('imei1')->nullable();
            $table->string('imei2')->nullable();
            $table->string('serial_number')->nullable();
            $table->text('hardware_version')->nullable();
            $table->text('qr_code')->nullable();
            $table->text('include')->nullable();
            $table->text('gemco')->nullable();
            $table->text('seal')->nullable();
            $table->text('fm')->nullable();
            $table->text('UniqueID')->nullable();
            $table->text('patrimony')->nullable();
            $table->text('observation')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->string('guarantee')->default('fora de garantia');
            $table->string('status')->default('pendente');
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('screening_report');
        Schema::dropIfExists('screening');
        Schema::dropIfExists('customers');
    }
};
