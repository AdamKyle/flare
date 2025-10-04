import {
  formatNumberWithCommas,
  formatPercent,
} from '../../../../../util/format-number';

export const attackToolTipDescription = (attack: number) => {
  let direction = null;

  if (attack < 0 || attack > 0) {
    direction = attack > 0 ? 'increase' : 'decrease';
  }

  const formattedAttack = formatNumberWithCommas(attack);

  if (!direction) {
    return `This item does not deal damage. Any attached Base Damage Modifier 
    % attached will instead be added to your over all Weapon Attack And Spell Damage stats.`;
  }

  return `This will ${direction} the weapon or spell damage when using Attack 
    or Attack and Cast (if a weapon) and Cast, Attack and Cast or Cast and Attack is a spell, 
    by a total of: ${formattedAttack}. This value includes any Damage Bonus Modifies listed on the item.
    `;
};

export const baseDamageModifierToolTipDescription = (
  baseDamageModifier: number
) => {
  let direction = null;

  if (baseDamageModifier < 0 || baseDamageModifier > 0) {
    direction = baseDamageModifier > 0 ? 'increase' : 'decrease';
  }

  const formattedBaseDamageAmount = formatPercent(baseDamageModifier).replace(
    /^[+-]/,
    ''
  );

  if (!direction) {
    return `This item does not have any modifiers that increase the base damage of the item.`;
  }

  return (
    `This will ${direction} the weapon or spell base damage by ${formattedBaseDamageAmount}. ` +
    `This can stack with other gear that contains this modifier to affect your overall damage, ` +
    `even if that gear doesn’t increase your damage.`
  );
};

export const baseDefenceToolTipDescription = (defence: number) => {
  let direction = null;

  if (defence < 0 || defence > 0) {
    direction = defence > 0 ? 'increase' : 'decrease';
  }

  const formattedDefence = formatNumberWithCommas(defence);

  if (!direction) {
    return `This item does not increase your defence. Any attached Base AC Modifier 
    % attached will instead be added to your over all defence.`;
  }

  return `This will ${direction} the total value of your defence and 
  includes any attached Base AC Modifier % in the total amount of: ${formattedDefence}. All Defence Values stack together
  to create a total AC (Armour Class) which is how much damage you can block when a monster attacks in a regular attack 
  or counter, but not an Ambush. The higher the value the more chance you have to survive.`;
};

export const baseAcModToolTipDescription = (defenceMod: number | null) => {
  let direction = null;

  if (!defenceMod) {
    return `This item does not have any modifiers that increase the base ac of the item.`;
  }

  if (defenceMod < 0 || defenceMod > 0) {
    direction = defenceMod > 0 ? 'increase' : 'decrease';
  }

  const formattedDefenceMod = formatPercent(defenceMod).replace(/^[+-]/, '');

  if (!direction) {
    return `This item does not have any modifiers that increase the base ac of the item.`;
  }

  return (
    `This will ${direction} the armour base AC by ${formattedDefenceMod}. ` +
    `This can stack with other gear that contains this modifier to affect your overall defence, ` +
    `even if that gear doesn’t increase your defence.`
  );
};

export const baseHealingToolTipDescription = (healing: number) => {
  let direction = null;

  if (healing < 0 || healing > 0) {
    direction = healing > 0 ? 'increase' : 'decrease';
  }

  const formattedHealing = formatNumberWithCommas(healing);

  if (!direction) {
    return `This item does not increase your healing. Any attached Base Healing Modifier 
    % attached will instead be added to your over all Healing Amount.`;
  }

  return `This will ${direction} the total value of your healing and 
  includes any attached Base Healing Modifier % in the total amount of: ${formattedHealing}. Healing is done via Cast, Attack and Cast or Cast and Attack while
  a Healing spell is equipped. If you have two healing spells, you have a greater chance of being resurrected on death, as well as healing for more.`;
};

export const baseHealingModifierToolTipDescription = (
  healingMod: number | null
) => {
  let direction = null;

  if (!healingMod) {
    return `This item does not have any modifiers that increase the base healing of the item.`;
  }

  if (healingMod < 0 || healingMod > 0) {
    direction = healingMod > 0 ? 'increase' : 'decrease';
  }

  const formattedHealingMod = formatPercent(healingMod).replace(/^[+-]/, '');

  if (!direction) {
    return `This item does not have any modifiers that increase the base healing of the item.`;
  }

  return (
    `This will ${direction} the spell base healing by ${formattedHealingMod}. ` +
    `This can stack with other gear that contains this modifier to affect your overall healing, ` +
    `even if that gear doesn’t increase your healing.`
  );
};

export const buildStatToolTip = (stat: string, amount: number) => {
  let direction = null;

  if (amount < 0 || amount > 0) {
    direction = amount > 0 ? 'increase' : 'decrease';
  }

  const formattedHealing = formatPercent(amount);

  if (!direction) {
    return `This item does not increase your core attributes at all.`;
  }

  return `This will ${direction} the total value of your ${stat} by: ${formattedHealing}. This will stack with with other item that increases this stat.`;
};
