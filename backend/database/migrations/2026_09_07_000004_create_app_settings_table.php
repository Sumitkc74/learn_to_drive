<?php

use App\Models\AppSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        $now = now();
        DB::table('app_settings')->insert(collect(AppSetting::DEFAULTS)->map(fn ($value, $key) => ['key' => $key, 'value' => (string) $value, 'created_at' => $now, 'updated_at' => $now])->values()->all());
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
