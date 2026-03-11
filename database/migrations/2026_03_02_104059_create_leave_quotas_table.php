<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_quotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('compani_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->date('period_start');  
            $table->date('period_end');  
            $table->unsignedTinyInteger('total_quota')->default(4);
            $table->unsignedTinyInteger('used_days')->default(0);
            $table->timestamps();

            $table->unique(['employee_id', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_quotas');
    }
};