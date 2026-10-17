<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['Web Development', 'code', 'Build websites and web apps with HTML, CSS, JavaScript and modern frameworks.'],
            ['Data Science', 'database', 'Analyse data, build dashboards and uncover insights with Python and SQL.'],
            ['Artificial Intelligence', 'brain', 'Machine learning, large language models and practical AI skills.'],
            ['Design', 'palette', 'UI/UX, visual design and design systems.'],
            ['Business', 'briefcase', 'Entrepreneurship, management and strategy.'],
            ['Marketing', 'megaphone', 'Digital marketing, branding, content and growth.'],
            ['Personal Development', 'sparkles', 'Productivity, communication and career skills.'],
            ['Photography', 'camera', 'Photography, editing and visual storytelling.'],
        ];

        foreach ($categories as [$name, $icon, $description]) {
            Category::create(['name' => $name, 'icon' => $icon, 'description' => $description]);
        }
    }
}
