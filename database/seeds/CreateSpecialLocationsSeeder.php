<?php

use Illuminate\Database\Seeder;
use App\Flare\Models\Location;

class CreateSpecialLocationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $locations = [
            'Ruins of Kalith'      => 'Ancient ruins that once held treasures that would make even the most richest of nations jealous.',
            'Ruins of Lord Galith' => 'Once stood a mighty kingdom that ruled the lands. Now stands the ruins of a fallen dynasty.',
            'Bandits Cave'         => 'Who lurks here? Who could? Theifs and murders. Plunder their riches!',
            'Mysterious Cave'      => 'These lands are marked with places waiting to be explored. Dwelve in and fine out whats inside.',
            'Forgotten Plantation' => 'Once this plantation gave forth many a bountiful harvest. Now it lies forgotten. Who lived here?',
            'Ancient Ruins'        => 'The history of this place speaks of battles, war, love, lust and greed. No wonder it fell.',
        ];

        // Special Locations:
        foreach ($locations as $name => $description) {
            if ($name !== 'Ancient Ruins') {
                Location::create([
                    'name' => $name,
                    'description' => $description,
                    'x' => $this->getRandomY(),
                    'y' => $this->getRandomX(),
                ]);
            }
        }

        // Generic Locations:
        for ($i = 0; $i <= 20; $i++) {
            Location::create([
                'name' => 'Ancient Ruins',
                'description' => $locations['Ancient Ruins'],
                'x' => $this->getRandomY(),
                'y' => $this->getRandomX(),
            ]);
        }
    }

    protected function getRandomY(): int {
        $randomY = rand(32, 1984);

        if ($randomY % 16 === 0) {
            return $randomY;
        }

        return $this->getRandomY();
    }

    protected function getRandomX(): int {
        $randomX = rand(32, 1984);

        if ($randomX % 16 === 0) {
            return $randomX;
        }

        return $this->getRandomX();
    }
}
