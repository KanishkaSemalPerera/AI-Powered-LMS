<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo accounts. Every account uses the password "password".
 */
class UserSeeder extends Seeder
{
    public const PASSWORD = 'password';

    public function run(): void
    {
        $password = Hash::make(self::PASSWORD);

        $this->createUser('Alex Morgan', 'admin@lms.test', UserRole::Admin, $password, [
            'headline' => 'Platform Administrator',
        ]);

        $instructors = [
            ['Sarah Chen', 'Senior Full-Stack Engineer', 'Sarah has spent 12 years building web products at startups and scale-ups. She loves turning complex topics into clear, practical lessons.'],
            ['David Okafor', 'Data Scientist & ML Engineer', 'David builds machine learning systems for healthcare and fintech, and has taught data science to more than 20,000 learners.'],
            ['Maria Lopez', 'Product Designer & Brand Strategist', 'Maria designs digital products and brands for independent businesses. She believes good design should be accessible to everyone.'],
        ];

        foreach ($instructors as $index => [$name, $headline, $bio]) {
            $this->createUser($name, 'instructor'.($index + 1).'@lms.test', UserRole::Instructor, $password, [
                'headline' => $headline,
                'bio' => $bio,
            ]);
        }

        $students = [
            'Emma Wilson', 'Liam Patel', 'Olivia Nguyen', 'Noah Kim', 'Ava Rossi',
            'Ethan Silva', 'Mia Johansson', 'Lucas Haddad', 'Sophia Mensah', 'James Novak',
        ];

        foreach ($students as $index => $name) {
            $this->createUser($name, 'student'.($index + 1).'@lms.test', UserRole::Student, $password);
        }
    }

    /**
     * role/is_active are not mass assignable, so they are set explicitly.
     */
    private function createUser(string $name, string $email, UserRole $role, string $hashedPassword, array $extra = []): User
    {
        $user = new User(array_merge(['name' => $name, 'email' => $email], $extra));
        $user->forceFill([
            'password' => $hashedPassword,
            'role' => $role,
            'is_active' => true,
            'email_verified_at' => now(),
        ])->save();

        return $user;
    }
}
