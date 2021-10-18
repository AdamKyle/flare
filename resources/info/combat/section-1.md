# Combat

As of 1.1.9 combat for Tlessa has changed drastically in relation to how one attacks an enemy.

Before 1.1.9 you had one attack button, you would select the monster, attack and throw everything you had at the monster. This made for some powerful, broken, out of balance builds.

As of 1.1.9 you now have five different attack types:

- Attack
- Cast
- Cast and Attack
- Attack and Cast
- Defend

When you go on adventures, you will also have to select which attack type to use when entering the adventure. This attack type cannot be changed and used for all floors of the adventure.

<div class="mb-4">
    <a href="/storage/info/combat/images/attack.png" class="glightbox">
        <img src="/storage/info/combat/images/attack.png" class="img-fluid" />
    </a>
</div>

<div class="mb-4">
    <a href="/storage/info/combat/images/attack-info.png" class="glightbox">
        <img src="/storage/info/combat/images/attack-info.png" class="img-fluid" />
    </a>
</div>

It&#39;s important to pay attention to your class to determine which attack is best for you. Of course, Tlessa wants you to experiment. Let&#39;s go over the attack types:

## Attack

Requires you to level Accuracy and your class skill for added damage.

Clicking attack will use your best weapon unless you are a fighter. If you are a fighter, we will use both your weapons. Of course, your class skill for fighters has two ways you could go: Tank (weapon and shield) or Glass Cannon (Two weapons).

If you have no weapons equipped, we will use 2% of your primary damage stat. This will allow you with two shields, to attack.

When attacking, your artifacts, affixes and rings will fire.

You can still be resurrected if you have a healing spell equipped, but instead of healing with spells, you will only get 1 health for resurrecting. If you have life stealing affixes attached, these can also fire, however resurrecting and healing will only happen at the end of the enemies turn assuming the enemy is not dead.

Class skills have a chance to fire (to do damage) once during this attack. Class Skill bonuses are automatically applied assuming you follow its rules.

## Cast

Requires you to level Casting Accuracy

This is a Heretics, Prophets and Vampires best attack

Clicking cast will fire off both your damage and healing spells. If you have two damage spells, you will use both. Same if you have two healing spells.

Casters get 15% (30% for heretics) of their damage stat added towards their attack.

When determining if a caster can hit, we use the casters focus (25% of) + their casting accuracy against the enemy&#39;s dodge.

Prophets get 30% towards their healing spells (a total of 32% if they have no healing spell) and Rangers get 15% (total of 17% of their chr if you have no healing spell) towards their healing spells.

Casters get 2% of their damage stat as a cast attack if they have no damage spells equipped (and 30% of their int for a total of 32% if you are a heretic). This is the only class(es) that can cast without spells.

Rangers can use their healing without having a healing spell equipped, but this attack is useless for them.

Class skills (damage) have a chance to twice here for casters and vampires. Once for the spell damage and once for the healing spell.

Casters will want two damage spells, whereas vampires will want one of each and two shields for added dur.

When casting it&#39;s your spells then your rings, artifacts and affixes

## Cast and Attack and Attack and Cast

Requires Both Casting Accuracy and Accuracy

Rangers and thieves might appreciate this attack.

Cast and attack will first cast with the spell in **spell slot one** and the weapon in **the right hand.**

If you have a bow equipped you will use that, regardless of which hand it&#39;s in as bows are duel wield weapons.

If an enemy blocks your cast, it will block both weapon and cast. You will fumble with your weapon and miss with your spell.

However, if your spell is a healing spell, even if it states your damage spell missed, and you fumbled with your weapon – your healing spell will still fire.

The same holds true if you miss. If you &quot;miss&quot; with your damage spell, you will also miss with your weapon, but your healing spell can still fire. This assumes the healing spell is in slot one.

If you do manage to cast, your weapon can then either miss or be blocked.

Note: The reason missed is in quotes, is because even if you have a bow and a healing spell, we still must see if you can &quot;cast&quot;, even though healing spells can never miss. This allows us to say you missed with your weapon and spell or (in the case that you were blocked) that you were blocked with your weapon and spell.

For Attack and cast, it&#39;s the same but reversed. Left-hand weapon then spell **slot two** will fire. The same concept applies as it does for cast and attack.

You have a chance for your class skill to fire twice if you are a vampire and once otherwise. For the vampire class skill to have a chance to fire twice, your weapon and damage spell cannot miss else it&#39;s a one-time chance (if you have a healing spell equipped in the appropriate slot)

## Defend

Best for Fighters, require no skill.

Defend will use 15% of the fighter&#39;s strength on top of their armour class or 5% of your strength on top of the armour class if you are not a fighter.

When you use this attack option, you will muster all the strength you have to block not just the enemies attack but potentially their spells as well.

After which your affixes and artifacts will also fire.

No class skills would fire on defend.

## Regarding Voidance

If you are voided at any time during the attack, we will fall back to raw values for stats and items you use in the attack. 
This means no affixes, no modded stats and no boons.

## Regarding Life Stealing Affixes That Stack

Vampires are the only class who have life stealing skills that can stack:

Vampires life stealing affixes do stack, but its 100% of the first ones damage and then 50% for each additional one divided by
4 and subtracted for 100 to get your total damage. Here's an example:

```
    // Assume you have 5 suffixes for life stealing, Vampires are the only class where these affixes stack.
    // Lets assume all 5 are at 25% of the enemies durability.

    suffixTotal = 0.25 * (0.175 * 0.175 * 0.175) * 100 // => ~13%

    // Assume you have two prefixes at 25% and 2 at 10%:
    prefixTotal = 0.25 * (0.175 * 0.05 * 0.05) * 100 // => ~1%

    suffix + prefix = 14.49%
```

The order of your prefixes and suffixes do not matter as we re-arrange the damage values for you and always start with the highest.

## Attacking Skills

When it comes to combat, depending on the attack and your class, you will want to focus on either Accuracy or Casting Accuracy or both.

Criticality is another skill that when leveled, based on its skill bonus, has a chance to let you do twice the damage. Enemies also have this skill a long with accuracy and casting accuracy.

Each class skill for each character now can modify your base damage, so you will also want to focus some time on leveling that skill.

**Which one do I do first**

Focus on either Accuracy or Casting Accuracy for the first 100 or so skill levels to make sure you can hit something.

Then do your class skill followed by your Criticality for a chance to do double damage.