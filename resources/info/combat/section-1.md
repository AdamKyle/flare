# Combat

Tlessa at it's core is all about fighting monsters to get better gear to take on harder creatures. There are various [affixes](/information/enchanting)
that can effect gear and thus your modded stats. When you equip gear that increases a stat by x%, we do not increase your base stat, instead
we add all the bonuses onto the stat, one at a time:

    $stat = $stat + $stat * $bonus

This done in a loop, thus your stats can get very high very quickly. On top of this is layered things like your skills base damage modifier
or your armour class modifier.

When your character attacks, we break down your attack for you:

<div class="mb-4">
    <a href="/storage/info/combat/images/attack-info.png" class="glightbox">
        <img src="/storage/info/combat/images/attack-info.png" class="img-fluid" />
    </a>
</div>

The more types of items you have equipped the more damage you can do. Each item in the shop will increase your stats. 
Specific affixes that you can only attach via enchanting, can add increased power on top of this.

> ### ATTN!
> 
> A character fully decked out in shop gear, with no affixes, can make it to the bottom of the surface critter list and most of the way
> down Labyrinth and Dungeons list. The first [Celestial Entity](/information/celestials) for surface is also able to be
> taken down by characters with high stats.
> 
> Harder creatures will involve you training crafting and enchanting to make even more powerful craft only gear
> that will help in taking these harder creatures down.

## Core Concept

Kill the creature in one hit and be able to attack again as fast as possible. 

This where skills that effect Fight Timout Modifier
or gear that effect the skill (a combination of both) comes in handy in relation to the damage you can do and avoid taking.

The faster you can kill = the more you kill = more rewards.

## Stats and Level matter.

Having the best gear is great, but first you will have to work your way there. Every character has two stats to focus on and a class skill
that increases their damage, healing, ac, fight time out modifiers or a combination of the aforementioned.

As you level, you get 1 point in each stat and 2 in your primary damage stat. You can see your damage stat on the [character sheet](/information/character-stats).

This combined with gear, affixes and skill bonuses will power your character up the proverbial ladder.

## How Does Attack Work?

The attack formula:

    hitChance = ((toHit + toHit * accuracy) / 100);

    return (enemyDex + enemyDex * dodge) - hitChance; // Gives percentage

That is to see if you can hit, if you can hit, we then check if your base stat, the damage stat, is greater than their ac or not.

Should you miss, your artifacts and spells as well as rings will then fire. Even if you do not miss, your spells, rings and artifacts will fire. The same thing can happen for enemies, their spells can fire as well.
Your healing will kick in only if you are in need of healing.

Because healing spells have a chance to resurrect you upon death, if you die, that chance, combined from items and class 
based bonuses is compared to see if you can automatically resurrect and fight again.

Prophets have an automatic 5% chance to resurrect.

If you have two healing spells at 70% chance to resurrect that's an instant resurrect as the percentage chance is now 140%.

## Spells, Artifacts and Rings matter

As mentioned earlier, spells, artifacts and rings can fire independently of weather you miss or not. Rings will increase spell evasion and artifact annulment to
reduce the enemies spells and artifacts. Normal Critters will not pose an issue, however [Celestial Entities](/information/celestials) will have spell evasion and artifact annulment as well as be able to cast
spells and use artifacts.

## Spell Evasion and Artifact annulment

These two stats only effect your ability to reduce the enemies spell damage and artifact damage that an enemy can have. While regular critters, that is
from the drop-down list (unless otherwise specified) do not have spells or artifacts that fire, [Celestial Entities](/information/celestials) do.

As mentioned, rings will increase this evasion. If you manage to have over 100% then you will take no spell or artifact damage.

## Bows

Bows are a new weapon type in Tlessa that allow the character to attack faster. These are the only weapons that affect fight time out modifier across all
skills that have a fight time out modifier bonus.

Bows also only increase AGI and Dex unlike other weapons that increase all stats except for Focus and Agi. Bows are duel welded and will
not allow you to have another weapon equipped. Bows are generally for Rangers.

## Class Skills

When it comes to combat, accuracy and you to hit stat, along with your damage stat are the vital aspects of the game - the higher these values, the better the
chance to hit, however some class skills effect aspects of your character: For example having a shield and weapon equipped as a fighter gives you
your class skills attack and defence bonus modifiers, however choosing to duel wield two weapons only gets your attack bonus.

Class Skills raise a set of bonuses and modifiers over time while fighting creatures. It is suggested that players go for this skill and accuracy, 
flipping back and forth as they level. However, players are free to level what ever skills they choose, in any order.


