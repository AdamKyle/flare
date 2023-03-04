<?php

namespace App\Console\Commands;

use App\Flare\Models\ReleaseNote;
use Illuminate\Console\Command;

class HandleReleaseNotes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'handle:release-notes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void {
        ReleaseNote::create([
            'name' => "More Classes!",
            'url' => "https://github.com/AdamKyle/flare/releases/tag/1.4.2",
            'version' => "1.4.2",
            'release_date' => "2023-03-04",
            'body' => "Welcome back all to Planes of Tlessa! Today I am excited to share the 1.4.2 changes and updates.

Lets start with the new classes. It is important to note these classes cannot be selected upon registration and instead require you to use the [Class Rank](https://planesoftlessa.com/information/class-ranks) system to unlock them by leveling specific classes.

It is also important to note that these classes do not come with enchantments, they can use any enchantment.

## [Prisoner](https://planesoftlessa.com/information/class/9)

[Prisoners](https://planesoftlessa.com/information/class/9) are a mix of thief and fighter and when using a weapon they have a chance to do 15% of their 15% of their strength 1-4 times for a rage attack against the enemy.

Prisoners are best with weapons of any type.

## [Alcoholic](https://planesoftlessa.com/information/class/10)

Alcoholics are drunkards who puke blood across their enemies. They are horrible with weapons suffering 25% damage deduction. Exceptionally horrible with spells, suffering 50% damage/healing reduction (although resurrection chance on healing spells is not effected).

How do they fight? bare handed! Their [class specials](https://planesoftlessa.com/information/class/10) focus mostly on raising their health and their damage.

Fighting barehanded gives you an additional 25% of their damage stat towards their attack.

Their special, which will fire if they have no weapons AND no spells (damage or healing) equipped will do 30% of your health as damage. However there is a downside, you will suffer 15% of your own health as damage in return.

These are one of the hardest classes to play. Alcoholics are a mix of Fighters and Blacksmiths.

## [Merchant](https://planesoftlessa.com/information/class/11)

One of the best classes to play, who are most proficient in staves with bows as secondary.

[Merchants](https://planesoftlessa.com/information/class/11) get a discount of 25% of at the shop, they also get 25% off the total bulk price of buying multiple items.

They get 30% reduction on crafting, 15% on enchanting, 10% on alchemy and trinketry and 5% off at specialty shops for Purgatory and Hell Forged gear.

They also get 5% reduction on crafting timers for all the above crafting types.

A merchants special attack (which you have chance of auto landing when attacking) allows them to flip a coin and either do 2x their damage or 4x times their damage.

Merchants are a cross between Blacksmiths and Rangers.

When the Merchant opens crafting, enchanting, alchemy, trinketry or special shops, the player will see in the server messages section the discounts and some of the drop downs will also show reduction to the items.

others, like enchanting, will apply the cost reduction after the total cost has been calculated.

## Mobile Fixes

The action section has been better designed for mobile, as well as various other fixes such as teleporting modal closing when on mobile and smaller devices.

Other adjustments to tables and various other aspects of the game in regards to mobile have also been fixed.

This is not the end of the mobile fixes, for the next patch will bring even more fixes to make sure the game works on phones, tablets, smaller monitors and so on.

## [Reincarnation](https://planesoftlessa.com/information/reincarnation)

[Reincarnation](https://planesoftlessa.com/information/reincarnation) has been increases to allow your stats to go to a max of
9,999,999,999. The player can then level from 1-5000, after they max their stats, for additional % towards their stats.

This allows players to take on and kill (when it gets there) Rank 50 creatures who have a max health of 750 Billion.

This will take roughly 90 reincarnations if you start at level 5000, with a total XP Penalty of 450%.

## Class Ranks Modal

The class ranks modal has been fixed to allow for players to to select a filter based on the class, for both the class specialties and \"Your other specialties\".

the filters will now properly stay in place when equipping or unequipping.

## Other Fixes

- Fixed issues with Purgatory and Hell Forged shops duplicating items in the list when players buy items that are replacing items with no enchants or holy oils applied.
- Fixed Duplicate and incorrectly spelled Quest Items
- Updated Ranked Fight Monsters to have slightly less Ambush and Counter Chance and resistance to give players more of a chance.
- Fixed classes so they properly respect the [races and classes](https://planesoftlessa.com/information/races-and-classes) docs.
	+ [Arcane Alchemists](https://planesoftlessa.com/information/class/8) no longer get damage bonus from having healing spells equipped
		* Their special attack now properly works.

- Added class bonus to the [enchantments](https://planesoftlessa.com/information/enchanting) table so players can find gear that raises their class bonus so their classes special attack lands.",
        ]);
    }
}
