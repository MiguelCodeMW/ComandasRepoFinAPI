<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        Setting::updateOrCreate(
            ['key' => 'global_iva'],
            ['value' => '0.21'] // IVA por defecto del 21%
        );

        Setting::updateOrCreate(
            ['key' => 'global_currency'],
            ['value' => 'EUR'] // Moneda por defecto EUR
        );
    }
}