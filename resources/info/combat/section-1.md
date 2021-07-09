# Combat
Tlessa is all about stats. Increasing your stats to do more damage, to fight harder critters and to get more drops.

Tlessa, to do all this, uses combat - like any other game – to push you forward. Tlessa draws heavy for inspiration on games like Disgea for its combat. Those familiar with the series will know that there is not much in the way of tactical elements to Tlessa.

This makes Tlessa a very grindy game. You grind for stats, you grind skill levels, you grind for coin and drops. All to make yourself better.

You attack, you kill, or you die, and you repeat.

What is going on behind the scenes when it comes to attacking and how much does your stats really impact your ability to fight?

## Core Concept

When it comes to combat, the core concept is for you to be able to kill the monster in **one hit**. Not many. You want to kill t, attack again, kill it, attack again. If you find that you cannot kill a creature in one hit, its because of your gear and/or your level.

## Stats and Level matter.

With in Tlessa, you have a set level to reach, this is called the cap. Each level gets you 1 point into your stats and 2 points into your class core stat.
With no equipment, by the time you reach level 1000, your stats will be around 1-2k

This will let you fight certain enemies down the list, but to get to the rest we need equipment. The base equipment you can buy from the shop will only take you so far. You must then enchant this gear with enchants that make sense to your goal (max level? Maxing skills? Crafting? Enchanting?).
This then will also only take you so far. If you have been training your crafting and enchanting, and you hit 220, you will end up crafting gear you cannot buy anywhere else. That is even better and raises the stat% to over 100% on some pieces.
Then you will need to enchant this gear as well with top tier enchantments.

You might have gotten some gear from drops when you were fighting or adventuring. This gear is capped at a specific amount, gold wise and so are the enchantments that come with them. This “starter” gear will only take you so far before you must start either buying (market or shop) or making it yourself.
A level one character could outfit them selves with the best gear money could buy – and still not be able to make it extremely far down the list – because you need to level. This can mean, sometimes you fight the same beast over and over and repeatedly.

## How Does Attack Work?

The attack formula:

    const attack = (attacker.base_stat + Math.round(attacker.dex / 2)) * attackerAccuracy;
    const dodge  = (defender.base_stat + Math.round(defender.dex / 2)) * defenderDodge;

    return attack > dodge;

That is to see if you can hit, if you can hit, we then check if your base stat, the damage stat, is greater then their ac or not.

Should you miss, your artifacts and spells will then fire. Even if you do not miss, your spells will fire. The same thing can happen for enemies, their spells can fire as well.
Your healing will kick in if their spells and artifacts fire and/or after their attack.
## Healing

Because of how healing works, you might think: Christ, I can fight the hardest creature and just click and click and click till its dead.

That is not how healing works. Healing will kick in only if you are not dead, that is the enemy did not do enough damage to kill you.

Some of the more expensive healing spells can (with a small chance) revive and then heal you.

Your CHR stat is also used in determining if you get a bonus to healing. To do that we take your mudded CHR and divide it from enemy attack to determine the percentage chance and then you roll, add the percentage to the roll and then compare against: Enemy Attack – (Enemy Attack / CHR Mod). If the number is higher then the value from the formula, you will be revived and healed for only 50% of the total healing of that item (including all bonuses applied to healing)
This will only happen once. Should you fall again, you will have to revive as normal.

## Spells and Artifacts Matter
As mentioned earlier, spells and artifacts will fire independently of you attacking with your weapon. So if a creature is giving you a hard time, equip a damaging spell, healing spell or artifact that does damage to help kill it faster.


