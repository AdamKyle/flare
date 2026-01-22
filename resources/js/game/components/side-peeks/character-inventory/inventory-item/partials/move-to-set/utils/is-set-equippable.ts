import {
  armourPositions,
  InventoryItemTypes,
} from '../../../../../../character-sheet/partials/character-inventory/enums/inventory-item-types';
import UseGetSetEquippabilityResponse from '../../../api/definitions/use-get-set-equippability-response-definition';

const buildCountsByType = (setItems: UseGetSetEquippabilityResponse[]) => {
  return new Map<InventoryItemTypes, number>(
    setItems.map((setItem) => [setItem.type, setItem.count])
  );
};

const countOf = (
  countsByType: Map<InventoryItemTypes, number>,
  itemType: InventoryItemTypes
) => {
  return countsByType.get(itemType) || 0;
};

const sumOf = (
  countsByType: Map<InventoryItemTypes, number>,
  itemTypes: InventoryItemTypes[]
) => {
  return itemTypes.reduce((total, itemType) => {
    return total + countOf(countsByType, itemType);
  }, 0);
};

export const isSetEquippable = (setItems: UseGetSetEquippabilityResponse[]) => {
  const countsByType = buildCountsByType(setItems);

  const weaponTypes = [
    InventoryItemTypes.BOW,
    InventoryItemTypes.CENSOR,
    InventoryItemTypes.CLAW,
    InventoryItemTypes.DAGGER,
    InventoryItemTypes.FAN,
    InventoryItemTypes.GUN,
    InventoryItemTypes.HAMMER,
    InventoryItemTypes.MACE,
    InventoryItemTypes.SCRATCH_AWL,
    InventoryItemTypes.STAVE,
    InventoryItemTypes.SWORD,
    InventoryItemTypes.WAND,
  ];

  const dualWieldWeaponTypes = [
    InventoryItemTypes.BOW,
    InventoryItemTypes.HAMMER,
    InventoryItemTypes.STAVE,
  ];

  const weaponCount = sumOf(countsByType, weaponTypes);
  const dualWieldCount = sumOf(countsByType, dualWieldWeaponTypes);
  const shieldCount = countOf(countsByType, InventoryItemTypes.SHIELD);

  const handsViolation =
    dualWieldCount > 0
      ? dualWieldCount !== 1 || shieldCount > 0 || weaponCount !== 1
      : weaponCount > 2 ||
        shieldCount > 2 ||
        (weaponCount > 0 && shieldCount > 0);

  const armourViolation = armourPositions.some((armourType) => {
    return countOf(countsByType, armourType) > 1;
  });

  const ringCount = sumOf(countsByType, [
    InventoryItemTypes.RING,
    InventoryItemTypes.RING_ONE,
    InventoryItemTypes.RING_TWO,
  ]);

  const spellCount = sumOf(countsByType, [
    InventoryItemTypes.SPELL_DAMAGE,
    InventoryItemTypes.SPELL_HEALING,
    InventoryItemTypes.SPELL_ONE,
    InventoryItemTypes.SPELL_TWO,
  ]);

  const trinketCount = countOf(countsByType, InventoryItemTypes.TRINKET);

  return (
    !handsViolation &&
    !armourViolation &&
    ringCount <= 2 &&
    trinketCount <= 1 &&
    spellCount <= 2
  );
};
