<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            ['key' => 'firm_name', 'value' => 'Sagardutt Pepsi Distributors'],
            ['key' => 'firm_address', 'value' => 'Daman, UT of Daman & Diu'],
            ['key' => 'firm_gstin', 'value' => '26AXXXX0000X1ZX'],
            ['key' => 'firm_phone', 'value' => ''],
            ['key' => 'firm_email', 'value' => ''],
            ['key' => 'firm_fssai', 'value' => ''],
            ['key' => 'bank_name', 'value' => 'IDBI BANK'],
            ['key' => 'bank_account_name', 'value' => 'SAGARDUTT PEPSI DISTRIBUTORS'],
            ['key' => 'bank_account_number', 'value' => '3181102000000550'],
            ['key' => 'bank_ifsc', 'value' => 'IBKL0000318'],
            ['key' => 'bank_branch', 'value' => 'NANI DAMAN'],
            ['key' => 'invoice_prefix', 'value' => 'SD'],
            ['key' => 'financial_year', 'value' => '25-26'],
            ['key' => 'next_invoice_seq', 'value' => '1'],
            ['key' => 'default_salesman', 'value' => 'JIGAR'],
            ['key' => 'enable_customer_import', 'value' => '1'],
            ['key' => 'invoice_qr_path', 'value' => ''],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], ['value' => $setting['value']]);
        }
    }
}
