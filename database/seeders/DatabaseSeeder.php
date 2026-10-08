<?php

namespace Database\Seeders;

use App\Domain\Events\OfficialCatalog;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        OfficialCatalog::sync();

        User::factory()->create([
            'name' => 'Ana Produtora',
            'email' => 'ana@partyos.test',
        ]);
    }
}
