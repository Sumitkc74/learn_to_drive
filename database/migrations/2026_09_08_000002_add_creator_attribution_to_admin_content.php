<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'users',
        'questions',
        'notices',
        'tutorials',
        'traffic_signs',
        'vision_tests',
        'exam_papers',
        'exam_information',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                // Keep the ID even if an administrator account is later removed so
                // the interface can distinguish a former admin from a legacy record.
                $table->unsignedBigInteger('created_by')->nullable()->after('id')->index();
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn('created_by');
            });
        }
    }
};
