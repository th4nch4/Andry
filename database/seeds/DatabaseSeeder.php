<?php

use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Guarded so re-seeding an existing database does not trip the unique email index.
        if (! User::where('email', 'admin@gmail.com')->exists()) {
            factory(User::class)->create([
                'name' => 'Admin',
                'email' => 'admin@gmail.com',
                'password' => Hash::make('12345'),
            ]);
        }
    }
}
