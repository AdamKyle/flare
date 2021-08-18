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
With no equipment, by the time you reach level 1000, your stats will be around 1-2k

This will let you fight certain enemies down the list, but to get to the rest we need equipment. The base equipment you can buy from the shop will only take you so far. You must then enchant this gear with enchantments that make sense to your goal (max level? Maxing skills? Crafting? Enchanting?).
This then will also only take you so far. If you have been training your crafting and enchanting, and you hit 220, you will end up crafting gear you cannot buy anywhere else. That is even better and raises the stat% to over 100% on some pieces.
Then you will need to enchant this gear as well with top tier enchantments.

You might have gotten some gear from drops when you were fighting or adventuring. This gear is capped at a specific amount, gold wise and so are the enchantments that come with them. This “starter” gear will only take you so far before you must start either buying (market or shop) or making it yourself.
A level one character could outfit themselves with the best gear money could buy – and still not be able to make it to the bottom of list or be able to take on stronger [Celestial Entities]() – because you need to level. This can mean, sometimes you fight the same beast over and over and repeatedly.

## How Does Attack Work?

The attack formula:

    const attack = (attacker.base_stat + Math.round(attacker.dex / 2)) * attackerAccuracy;

    return attack > enemyDodge;

That is to see if you can hit, if you can hit, we then check if your base stat, the damage stat, is greater than their ac or not.

Should you miss, your artifacts and spells as well as rings will then fire. Even if you do not miss, your spells, rings and artifacts will fire. The same thing can happen for enemies, their spells can fire as well.
Your healing will kick in only if you are in need of healing.

Because healing spells have a chance to resurrect you upon death, if you die, that chance, combined from items and class 
based bonuses is compared to see if you can automatically resurrect and fight again.

## Spells, Artifacts and Rings matter

As mentioned earlier, spells, artifacts and rings can fire independently of weather you miss or not. Rings will increase spell evasion and artifact annulment to
reduce the enemies spells and artifacts. Normal Critters will not pose an issue, however [Celestial Entities]() will have spell evasion and artifact annulment as well as be able to cast
spells and use artifacts.

Healing Spells have a chance to resurrect th player and stack if you have two healing spells equipped.


