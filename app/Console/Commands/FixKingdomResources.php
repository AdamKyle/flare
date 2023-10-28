<?php

namespace App\Console\Commands;

use App\Flare\Models\Kingdom;
use Illuminate\Console\Command;

class FixKingdomResources extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:kingdom-resources';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fixes kingdom resources who might be below 0';

    /**
     * Execute the console command.
     */
    public function handle() {
        $kingdomsWithResourcesBelowZero = Kingdom::where(function ($query) {
            $query->where('current_stone', '<', 0)
                ->orWhere('current_wood', '<', 0)
                ->orWhere('current_clay', '<', 0)
                ->orWhere('current_iron', '<', 0)
                ->orWhere('current_steel', '<', 0)
                ->orWhere('current_population', '<', 0);
        })->get();

        foreach ($kingdomsWithResourcesBelowZero as $kingdom) {
            $attributesToUpdate = [
                'current_stone',
                'current_wood',
                'current_clay',
                'current_iron',
                'current_steel',
                'current_population',
            ];

            foreach ($attributesToUpdate as $attribute) {
                if ($kingdom->{$attribute} < 0) {
                    $kingdom->{$attribute} = max(0, $kingdom->$attribute);
                }
            }

            $kingdom->save();
        }
    }
}
