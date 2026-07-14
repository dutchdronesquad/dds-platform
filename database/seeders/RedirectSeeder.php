<?php

namespace Database\Seeders;

use App\Models\Redirect;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RedirectSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $redirects = [
            [
                'source_path' => '/trainingen',
                'target_url' => '/events?type=training',
                'notes' => 'Legacy WordPress training page.',
            ],
            [
                'source_path' => '/trainingsdagen',
                'target_url' => '/events?type=training',
                'notes' => 'Historical alias for the training calendar.',
            ],
            [
                'source_path' => '/agenda',
                'target_url' => '/events',
                'notes' => 'Legacy public agenda entry point.',
            ],
            [
                'source_path' => '/nieuws',
                'target_url' => '/news',
                'notes' => 'Legacy Dutch news archive.',
            ],
            [
                'source_path' => '/huisregels',
                'target_url' => '/house-rules',
                'notes' => 'Legacy Dutch house rules page.',
            ],
        ];

        foreach ($redirects as $redirect) {
            Redirect::query()->updateOrCreate(
                ['source_path' => $redirect['source_path']],
                [
                    'target_url' => $redirect['target_url'],
                    'status_code' => 301,
                    'is_active' => true,
                    'notes' => $redirect['notes'],
                ],
            );
        }
    }
}
