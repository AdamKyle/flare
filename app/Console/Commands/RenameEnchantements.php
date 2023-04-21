<?php

namespace App\Console\Commands;

use App\Flare\Models\ItemAffix;
use Illuminate\Console\Command;

class RenameEnchantements extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rename:enchantments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rename Enchantments';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        $enchantmentNames = [
            "Devils Deliverence"            => "Devils Deliverance",
            "Medatative Hope"               => "Meditative Hope",
            "Lifes Dance"                   => "Life's Dance",
            "Cry of Maddness"               => "Cry of Madness",
            "Childs Wonder"                 => "Child's Wonder",
            "Ilussionary Work"              => "Illusionary Work",
            "Lifes Bloody Sigil"            => "Life's Bloody Sigill",
            "Heretics Maddness"             => "Heretics Madness",
            "Satans Delicious Flesh Ripper" => "Satan's Delicious Flesh Ripper",
            "Trained Bowmen's Blood"        => "Trained Bowman's Blood",
            "Devlish Strength"              => "Devilish Strength",
            "Dextrous Infatuation"          => "Dexterous Infatuation",
            "Demonic Acuracy"               => "Demonic Accuracy",
            "Blood Curtling Prayer"         => "Blood Curdling Prayer",
            "Dextrous Lies"                 => "Dexterous Lies",
            "Lifes Final Breath"            => "Life's Final Breath"
        ];

        foreach ($enchantmentNames as $old => $new) {
            ItemAffix::where('name', $old)->update(['name' => $new]);
        }
    }
}
