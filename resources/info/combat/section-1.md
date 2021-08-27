# Combat
Tlessa is all about stats. Increasing your stats to do more damage, to fight harder critters and to get more drops.

Tlessa, to do all this, uses combat - like any other game – to push you forward. Tlessa draws heavy for inspiration on games like Disgea for its combat. Those familiar with the series will know that there is not much in the way of tactical elements to Tlessa.

This makes Tlessa a very grindy game. You grind for stats, you grind skill levels, you grind for coin and drops. All to make yourself better. That's the aspect most might be familiar with.

To do this, you will click. You will click attack, the faster you get to click again the better, the more damage you do and the faster you can kill, the more XP, Drops, Gold and Skill Xp.

You attack, you kill, or you die, and you repeat.

What is going on behind the scenes when it comes to attacking and how much does your stats really impact your ability to fight?

## Core Concept

When it comes to combat, the core concept is for you to be able to kill the monster in **one hit**. Not many. You want to kill it, attack again, kill it, attack again. If you find that you cannot kill a creature in one hit, its because of your gear and/or your level.

## Stats and Level matter.

With in Tlessa, you have a set level to reach, this is called the cap. Each level gets you 1 point into your stats and 2 points into your class core stat.
With no equipment, by the time you reach level 1000, your stats will be around 1-5k

With the max shop equipment, that is gear at 2 Billion per piece, your stats will be much, much higher, around 40-100k. This will be enough to kill the strongest critter from the list
as well as the strongest [Celestial Entities](/information/celestials) in the game.

Beyond this is craft only sets, which cost between 2.1 Billion and 2.4 Billion per set item and cannot be bought from the shop. These require high level crafting skills and
would make you severely overpowered, add affixes towards that, and you are even stronger.


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


