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

        // Special Explorable Locations:
        $locations = [
            [
                'name'        => 'Ruins of Kalith',
                'description' => 'Ancient ruins that once held treasures that would make even the most richest of nations jealous.',
                'is_port'     => false,
                'x'           => $this->getRandomX(),
                'y'           => $this->getRandomY(),
            ],
            [
                'name'        => 'Ruins of Lord Galith',
                'description' => 'Once stood a mighty kingdom that ruled the lands. Now stands the ruins of a fallen dynasty.',
                'is_port'     => false,
                'x'           => $this->getRandomX(),
                'y'           => $this->getRandomY(),
            ],
            [
                'name'        => 'Bandits Cave',
                'description' => 'Who lurks here? Who could? Theifs and murders. Plunder their riches!',
                'is_port'     => false,
                'x'           => $this->getRandomX(),
                'y'           => $this->getRandomY(),
            ],
            [
                'name'        => 'Mysterious Cave',
                'description' => 'These lands are marked with places waiting to be explored. Dwelve in and fine out whats inside.',
                'is_port'     => false,
                'x'           => $this->getRandomX(),
                'y'           => $this->getRandomY(),
            ],
            [
                'name'        => 'Forgotten Plantation',
                'description' => 'Once this plantation gave forth many a bountiful harvest. Now it lies forgotten. Who lived here?',
                'is_port'     => false,
                'x'           => $this->getRandomX(),
                'y'           => $this->getRandomY(),
            ],
        ];

        Location::insert($locations);

        // Generic Locations:
        for ($i = 0; $i <= 120; $i++) {
            Location::create([
                'name'        => 'Ancient Ruins',
                'description' => 'The history of this place speaks of battles, war, love, lust and greed. No wonder it fell.',
                'is_port'     => false,
                'x'           => $this->getRandomY(),
                'y'           => $this->getRandomX(),
            ]);
        }

        // Ports:
        $ports = [
            [
                'name'        => 'Smugglers Port',
                'description' => 'A place where the stolen goods are smuggled in.',
                'is_port'     => true,
                'x'           => 824,
                'y'           => 856,
            ],
            [
                'name'        => 'Port of Kalith',
                'description' => 'Welcome to Kalith port! Adventure awaits!',
                'is_port'     => true,
                'x'           => 600,
                'y'           => 1096,
            ],
            [
                'name'        => 'Dalix',
                'description' => 'Dalix offers all your needs! Come to Dalix!',
                'is_port'     => true,
                'x'           => 896,
                'y'           => 1232,
            ],
            [
                'name'        => 'Port of Salix',
                'description' => 'The cousin of Dalix. A place where rules do not exist.',
                'is_port'     => true,
                'x'           => 1744,
                'y'           => 1184,
            ],
            [
                'name'        => 'Karth',
                'description' => 'The port of Karth is a majour trading port.',
                'is_port'     => true,
                'x'           => 448,
                'y'           => 1264,
            ],
            [
                'name'        => 'Deoth',
                'description' => 'Deoth contains the musuem of lost traeasures.',
                'is_port'     => true,
                'x'           => 480,
                'y'           => 1520,
            ],
            [
                'name'        => 'Halix',
                'description' => 'The port of Halix is home of the mystical beast monument.',
                'is_port'     => true,
                'x'           => 48,
                'y'           => 1120,
            ],
            [
                'name'        => 'Lavion',
                'description' => 'The best treasures are found on this island.',
                'is_port'     => true,
                'x'           => 64,
                'y'           => 1728,
            ],
            [
                'name'        => 'Rax',
                'description' => 'Lost port of Rax',
                'is_port'     => true,
                'x'           => 992,
                'y'           => 1424,
            ],
            [
                'name'        => 'Azyx',
                'description' => 'Ancient civilization of the Azyx people use to use this port.',
                'is_port'     => true,
                'x'           => 1520,
                'y'           => 1353,
            ],
            [
                'name'        => 'Jungle of Xynx Port',
                'description' => 'Leaving this port means adventurng deep into the jungle.',
                'is_port'     => true,
                'x'           => 528,
                'y'           => 1504,
            ],
            [
                'name'        => 'Edge of the world',
                'description' => 'The edge of the world port.',
                'is_port'     => true,
                'x'           => 1056,
                'y'           => 1712,
            ],
        ];

        Location::insert($ports);
    }

    protected function getRandomY(): int {
        $randomY = rand(32, 1984);

        if ($randomY % 16 === 0) {
            return $randomY + 16;
        }

        return $this->getRandomY();
    }

    protected function getRandomX(): int {
        $randomX = rand(32, 1984);

        if ($randomX % 16 === 0) {
            return $randomX + 16;
        }

        return $this->getRandomX();
    }
}
