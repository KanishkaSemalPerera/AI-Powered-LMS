<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the demo platform. Run with:  php artisan migrate:fresh --seed
     *
     * Demo logins (password "password" for all):
     *   admin@lms.test
     *   instructor1@lms.test … instructor3@lms.test
     *   student1@lms.test … student10@lms.test
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            CourseSeeder::class,
            DemoActivitySeeder::class,
        ]);
    }
}
