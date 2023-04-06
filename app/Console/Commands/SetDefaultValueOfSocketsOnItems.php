<?php

namespace App\Console\Commands;

use App\Flare\Models\Item;
use App\Flare\Values\ArmourTypes;
use App\Flare\Values\WeaponTypes;
use Illuminate\Console\Command;

class SetDefaultValueOfSocketsOnItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'set-default:sockets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set default sockets on items';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        return Item::whereIn('type', [
            WeaponTypes::WEAPON,
            WeaponTypes::STAVE,
            WeaponTypes::BOW,
            WeaponTypes::HAMMER,
            ArmourTypes::SHIELD,
            ArmourTypes::BODY,
            ArmourTypes::SLEEVES,
            ArmourTypes::HELMET,
            ArmourTypes::FEET,
            ArmourTypes::LEGGINGS,
            ArmourTypes::GLOVES
        ])->update(['socket_count' => 0]);
    }
}
