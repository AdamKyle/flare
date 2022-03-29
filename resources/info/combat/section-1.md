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

> ### ATTN!!
> 
> The info below will go left to right based on the first image above.

## Attack

Requires you to level Accuracy and your class skill for added damage.

Clicking attack will use your best weapon unless you are a fighter. If you are a fighter, we will use both your weapons. 
Of course, your class skill for fighters has two ways you could go: Tank (weapon and shield) or Glass Cannon (Two weapons).

Fighters with weapons will use 15% of their str, while thieves and rangers will use 5% with weapons equipped, of their primary damage stat.

If you have no weapons equipped, we will use 2% of your primary damage stat, whereas Fighters will use 5%. This will allow you with two shields, to attack.

When attacking, your artifacts, affixes and rings will fire.

You can still be resurrected if you have a healing spell equipped, but instead of healing with spells, you will only get 1 health for resurrecting. If you have life stealing affixes attached, these can also fire, 
however resurrecting and healing will only happen at the end of the enemies turn assuming the enemy is not dead.

Class skills have a chance to fire (to do damage) once during this attack. Class Skill bonuses are automatically applied assuming you follow its rules.

The only class skills that will not fire is the Heretics Double Cast and the Prophets Double heal, these require you to use one of the cast options.

**Best for:** Fighters, Thieves and Vampires.

> ### ATTN!
> 
> When you first start out this is your primary attack. You will want to start leveling accuracy till you can reliably hit, being blocked is fine - you just need more damage.

## Cast

Requires you to level Casting Accuracy

This is a Heretic's best attack.

Clicking cast will fire off both your damage and healing spells. If you have two damage spells, you will use both. Same if you have two healing spells.

Heretics get 30% of their int applied to spell damage, whereas they only get 2% for no spells equipped.

Prophets will get 30% of their chr for healing and rangers get 15% of their chr for healing. Even if there are no healing spells equipped.

In theory, Prophets could go glass cannon and use damage spells, but you would need to balance the CHR with the INT to not only do enough damage but also
heal enough.

When determining if a caster can hit, we use the casters focus (25% of) + their casting accuracy against the enemy&#39;s dodge.

Class skills (damage) have a chance to twice here for casters and vampires. Once for the spell damage and once for the healing spell.

Prophets Double Heal and Heretics Double Cast can both fire assuming you follow the class skill rules, when using this attack.

When casting it&#39;s your spells, we also fire off affixes, rings and artifacts.

**Best For:** Heretics

> ### ATTN!
>
> Do not use this when you first start out. Even if you are a heretic. Instead, equip some weapons and armour and use Attack.
> Once you have trained Accuracy to a level where you are actually hitting with your weapon, switch to Casting Accuracy
> and use Attack and Cast or Cast and Attack (below) till your spell reliably hits, then switch the attack type to cast.
> 
> Once you switch to cast, equip two damage spells and two shields (this is your glass cannon build). The shields will give you the added health,
> while the damage spells are your primary weapon.

## Cast and Attack and Attack and Cast

Requires Both Casting Accuracy and Accuracy

Cast and Attack will first cast with the spell in **spell slot one** and the weapon in **the right hand.**

Attack and Cast will first use the weapon in **the left hand** and the spell in **spell slot two.**

If you have a bow equipped you will use that, regardless of which hand it&#39;s in, as bows are duel wield weapons.

Enemies can block both attacks: Cast and then Attack (or Attack and Cast). They can also dodge both attacks.

Healing spells, will never miss. So if you have a Healing spell a bow equipped and the bow misses, the healing spell will still fire (before the bow).

Vampires with a damage or healing spell can trigger their Vampire Thirst Skill three times as they get their weapon, Spell and Affixes. Technically four
if they survive the enemy.

**Best For:** Prophets, Rangers, Vampires

- Vampires have three opportunities to fire off their class [skill](/information/skill-information) when using this attack.
- Rangers can use this in conjunction with a healing spell.
- Prophets will want this as they are mostly a healing class, so having the ability to attack and cast or cast and attack (attack and heal or heal and attack)
  will let you do damage as well as heal against any enemy attacks.

## Defend

Fighters will apply their class bonus to their defence.

When you use this option, you will muster all the strength you have to block not just the enemies attack but potentially their spells as well.

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

## Regarding Trinkets (Late Game)

*Trinkets fire automatically like other gear, such as affixes that do damage or rings that reduce resistances and so on ... These trigger at the start of the battle.*

Trinkets can be [crafted](/information/crafting) only when you have access to [Purgatory](/information/planes), which has creatures that drop copper coins.

These items, to which you can equip two of, introduce four new stats:

- Ambush Chance and Ambush Resistance %
- Counter Chance and Counter Resistance %

Ambush is similar to [Thieves](/information/races-and-classes) [Shadows Dance](/information/skills-information) in which you can bypass the enemy dodge and block and attack out right.

You can have a max of 75% Ambush Chance and Resistance. Only enemies in Purgatory have an Ambush Resistance and Chance, while most [Celestials](/information/celestials) have just resistances to both going to a max of 60%. 
If you manage to ambush an enemy, they cannot counter your attack, the same applies if a Thieves Shadow Dance triggers.

- Ambush will do 2x your modded damage stat and fires AFTER [voidance and devoidance](/information/voidance) and all reductions to the enemy BEFORE you get chance to click the attack buttons.
  - This does mean that Ambush can kill before you even get a chance to hit any attack button. 
  - If you die in battle and Ambush has initially fired, it will not fire again upon resurrection.

Counter allows you to counter the enemy's weapon attack at +5% to your weapon attack. If you manage to counter the enemy then has a 2% chance to counter your counter at +5% of their attack.

If the enemy counters your attack you have a 2% chance to counter their counter at +2.5% of your weapon attack.

You cannot counter a countered counter, that is:

- Enemy hits
- You counter - You must be alive after the initial attack for this to happen.
- Enemy Counters - The enemy must be alive after your counter for this to happen.
- Other enemy actions such as spells, affixes and so on. (only if the enemy lives after your counter)

> ### ATTN!
> 
> It does not matter what action you used to attack with: Attack, Cast, Cast and Attack or Attack and Cast. We will always use your weapon or a % of your strength
> to do damage.
> 
> If you attack with defend, you cannot counter.
> 
> Counters will only trigger if you or the enemy A) are alive and B) pass the difficulty check (out of 100).
> 
> When fighting monsters in Purgatory: Enemies will attempt to ambush FIRST. Whereas fighting creatures outside of purgatory, you will attempt to ambush first.
> 
> Finally, Ambushing takes place AFTER all reductions and [voidances](/information/voidance) are done, again at the beginning of the battle. This means if you were voided
> your **ambush will fail** however, your ambush resistance **will** still let you resist an ambush, but not your chance to counter, you will just counter at lower damage. 
> Being devoided will not affect your ambush or counter chances or resistances.
> Purgatory will not reduce or lower, nor will other planes or special locations, your ambush or counter chance or the enemies chances or resistances.
