# Combat

As of 1.1.9 combat for Tlessa has changed drastically in relation to how one attacks an enemy.

Before 1.1.9 you had one attack button, you would select the monster, attack and throw everything you had at the monster. 
This made for some powerful, broken, out of balance builds.

As of 1.1.9 you now have five different attack types:

- Attack
- Cast
- Cast and Attack
- Attack and Cast
- Defend

When you go on adventures, you will also have to select which attack type to use when entering the adventure. 
This attack type cannot be changed and is used for all floors of the adventure.

<div class="mb-4">
    <a href="/storage/info/combat/images/attack.png" class="glightbox">
        <img src="/storage/info/combat/images/attack.png" class="img-fluid" />
    </a>
</div>

Hovering over these will give you tool tips about whats involved in the attack.

<div class="mb-4">
    <a href="/storage/info/combat/images/attack-info.png" class="glightbox">
        <img src="/storage/info/combat/images/attack-info.png" class="img-fluid" />
    </a>
</div>

It&#39;s important to pay attention to your class to determine which attack is best for you. Of course, Tlessa wants you to experiment. Let&#39;s go over the attack types:

## Attack

Requires you to level Accuracy and your class skill for added damage.

Clicking attack will use your best weapon unless you are a fighter. If you are a fighter, we will use both your weapons. 
Of course, your class skill for fighters has two ways you could go: Tank (weapon and shield) or Glass Cannon (Two weapons).

If you are a fighter you can duel wield as we take into account two weapons for fighters. Fighters also use 15% of their strength for attacking.

If you have no weapons equipped, we will use 2% of your primary damage stat. This will allow you with two shields, to attack.

When attacking, your artifacts, affixes and rings will fire.

You can still be resurrected if you have a healing spell equipped, but instead of healing with spells, you will only get 1 health for resurrecting. If you have life stealing affixes attached, these can also fire, however resurrecting and healing will only happen at the end of the enemies turn assuming the enemy is not dead.

Class skills have a chance to fire (to do damage) once during this attack. Class Skill bonuses are automatically applied assuming you follow its rules.

Rangers, Thieves and Vampires best attack is some kind of attack option (or defend for fighters with damage dealing enchantment(s)). Fighters should stick to
Attack or Defend.

## Cast

Requires you to level Casting Accuracy

This is a Heretics, Prophets best attack.

Clicking cast will fire off both your damage and healing spells. If you have two damage spells, you will use both. Same if you have two healing spells.

Casters get 15% (30% for heretics - 32% with no spells) of their damage stat added towards their attack.

When determining if a caster can hit, we use the casters focus (25% of) + their casting accuracy against the enemy&#39;s dodge.

Prophets get 30% towards their healing spells (a total of 32% if they have no healing spell) and Rangers get 15% (total of 17% of their chr if you have no healing spell) towards their healing spells.

Casters get 2% of their damage stat as a cast attack if they have no damage spells equipped (and 30% of their int for a total of 32% if you are a heretic). This is the only class(es) that can cast without spells.

Rangers can use their healing without having a healing spell equipped, but this attack is useless for them.

Class skills (damage) have a chance to twice here for casters and vampires. Once for the spell damage and once for the healing spell.

Casters will want two damage spells, whereas vampires will want one of each and two shields for added dur. Prophets should use one damage and one healing depending on 
how hard the enemy is. They could also go the two durability shields (two shields with extra dur enchantments) and two attack spells.

When casting it&#39;s your spells then your rings, artifacts and affixes

### Regarding Casters

In your few level's avoid these attack types. Instead, focus on Attack. The reason is, you have a higher chance
to hit something then you do to cast. The exception is for prophets who use healing spells, your healing spells - even if your "damage" spell misses. will never
miss or be blocked.

## Cast and Attack and Attack and Cast

Requires Both Casting Accuracy and Accuracy

Rangers, Thieves and Vampires might appreciate this attack.

Cast and attack will first cast with the spell in **spell slot one** and the weapon in **the right hand.**

If you have a bow equipped you will use that, regardless of which hand it&#39;s in as bows are duel wield weapons.

If an enemy blocks your cast, it will block both weapon and cast. You will fumble with your weapon and miss with your spell.

However, if your spell is a healing spell, even if it states your damage spell missed, and you fumbled with your weapon – your healing spell will still fire.

The same holds true if you miss. If you &quot;miss&quot; with your damage spell, you will also miss with your weapon, but your healing spell can still fire. This assumes the healing spell is in slot one.

If you do manage to cast, your weapon can then either miss or be blocked.

Note: The reason missed is in quotes, is because even if you have a bow and a healing spell, we still must see if you can &quot;cast&quot;, even though healing spells can never miss. This allows us to say you missed with your weapon and spell or (in the case that you were blocked) that you were blocked with your weapon and spell.

For Attack and cast, it&#39;s the same but reversed. Left-hand weapon then spell **slot two** will fire. The same concept applies as it does for cast and attack.

You have a chance for your class skill to fire twice if you are a vampire and once otherwise. For the vampire class skill to have a chance to fire twice, your weapon and damage spell cannot miss else it&#39;s a one-time chance.

### Regarding Casters

In your few level's avoid these attack types. Instead, focus on Attack. The reason is, you have a higher chance
to hit something then you do to cast. The exception is for prophets who use healing spells, your healing spells - even if your "damage" spell misses. will never
miss or be blocked.

## Defend

Best for Fighters, require no skill.

Defend will use 15% of the fighter&#39;s strength on top of their armour class or 5% of your strength on top of the armour class if you are not a fighter.

When you use this attack option, you will muster all the strength you have to block not just the enemies attack but potentially their spells as well.

After which your affixes and artifacts will also fire.

No class skills would fire on defend.

## Regarding Voidance

If you are voided at any time during the attack, we will fall back to raw values for stats and items you use in the attack. 
This means no affixes, no modded stats and no boons. The [voidance](/information/voidance) will last the entire battle, even if you die and revive, you'll still be voided.
This will also apply to your enemy, however they cannot resurrect.

This does not hold true for adventures, celestials or planned future pvp.

## Regarding Devoidance

Enemies from the dropdown do not have a way to [devoid](/information/voidance) player. However, Celestials do. There is a quest item you can get
which, through upgrading over time, will increase your chance to devoid an enemy. Simply put, Devoid will void their attempt to void you.

If an enemy is devoided, they are devoided for the whole battle - even if you die. Your soul hold their enchants at bay.

## Regarding Life Stealing Affixes That Stack

Vampires are the only class where the [life stealing affixes](/information/enchanting) will stack. However, the damage is not 100% of what you would do.
Instead, the damage is your first most powerful life stealing, multiplied by 75% of the sum of the rest of the affixes. This percentage is the amount of
life you will steal from the enemy, which will scale with the enemies' health. The harder the enemy the more damage you do.

The problem is, Vampires do not do enough damage with life stealing alone, this is where you can either Attack or Cast. Casting will give you two chances, assuming your damage spell hits,
to fire off Vampire Thirst which can kill an enemy. Using Attack would do 5% of your Durability, with no weapons equipped. The higher the dur, the more damage.

Another thing to note about life stealing affixes is that they **will not fire if the weapon/spell damage you do, before the affixes, rings and artifacts would fire, kills the enemy**.
There would be no point to you stealing a dead enemies' health. Vampires will know if the enemy is too trivial when they're life stealing affixes do not fire.

You will never do 100% of an enemies' health, regardless of how good the enchantments are. The total Life you can steal at any one time is 99% of the enemies' health from 
Life Stealing enchantments.

## Rings can fire even if dead

Your rings can mitigate the enemies attempt to heal or to use their spells, even if you are dead. The lore around this is simple:

> Rings are magical items that do not need the character to be alive to fire off their reductions or evasions. 
> These rings have a mind of their own when it comes to these types of magic. However, a rings damage cannot fire if the character is dead,
> as the damaging aspect comes from the characters will to live, if they are dead, they have no will.

## Attacking Skills

When it comes to combat, depending on the attack and your class, you will want to focus on either Accuracy or Casting Accuracy or both.

Criticality is another skill that when leveled, based on its skill bonus, has a chance to let you do twice the damage. Enemies also have this skill a long with accuracy and casting accuracy.

Each class skill for each character now can modify your base damage, so you will also want to focus some time on leveling that skill.

**Which one do I do first**

Focus on either Accuracy or Casting Accuracy for the first 100 or so skill levels to make sure you can hit something.

Then do your class skill followed by your Criticality for a chance to do double damage.