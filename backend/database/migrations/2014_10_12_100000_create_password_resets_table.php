<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('password_resets', function (Blueprint $table) {
            // Aiven MySQL sets sql_require_primary_key, so a table without
            // a primary key cannot be created (error 3750). This legacy
            // table is unused — auth uses password_reset_tokens — but
            // making email the primary key (as in that table) keeps the
            // migration runnable on Aiven without changing any behaviour.
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('password_resets');
    }
};
