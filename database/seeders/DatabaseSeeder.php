<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Document;
use App\Models\User;
use Hash;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test users
        User::factory(10)
            ->create()
            ->each(function (User $user) {
                // Create mix of documents for each user
                Document::factory()->count(2)->create(['owner_id' => $user->id]);
                Document::factory()->expiringSoon()->count(1)->create(['owner_id' => $user->id]);
                Document::factory()->expired()->count(1)->create(['owner_id' => $user->id]);
                Document::factory()->expired()->archived()->count(1)->create(['owner_id' => $user->id]);
            });

        // Update first user with known credentials for testing
        User::where('id', 1)
            ->update([
                'email' => 'user@astalty.com.au',
                'password' => Hash::make('password'),
            ]);

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
