import { CraftSetPosition } from './craft-set-position';
import {
  CraftableItemCraftingType,
  CraftableItemSubtype,
} from '../../crafting/api/definitions/craftable-item-query-definition';
import { craftSetPositionLabel } from '../utils/batch-crafting-labels';

export interface CraftSetHandPositionOption {
  key: CraftSetPosition;
  label: string;
}

export interface CraftSetRequiredPositionOption {
  key: CraftSetPosition;
  label: string;
  crafting_type: CraftableItemCraftingType;
  armour_type?: CraftableItemSubtype;
  item_type?: CraftableItemSubtype;
}

const buildHandOption = (
  position: CraftSetPosition
): CraftSetHandPositionOption => ({
  key: position,
  label: craftSetPositionLabel(position) ?? position,
});

const buildRequiredOption = (
  position: CraftSetPosition,
  crafting_type: CraftableItemCraftingType,
  armour_type?: CraftableItemSubtype,
  item_type?: CraftableItemSubtype
): CraftSetRequiredPositionOption => ({
  key: position,
  label: craftSetPositionLabel(position) ?? position,
  crafting_type,
  armour_type,
  item_type,
});

export const CRAFT_SET_OPTIONAL_HAND_POSITIONS: CraftSetHandPositionOption[] = [
  buildHandOption(CraftSetPosition.LEFT_HAND),
  buildHandOption(CraftSetPosition.RIGHT_HAND),
];

export const CRAFT_SET_REQUIRED_POSITION_OPTIONS: CraftSetRequiredPositionOption[] =
  [
    buildRequiredOption(CraftSetPosition.BODY, 'armour', 'body'),
    buildRequiredOption(CraftSetPosition.LEGGINGS, 'armour', 'leggings'),
    buildRequiredOption(CraftSetPosition.SLEEVES, 'armour', 'sleeves'),
    buildRequiredOption(CraftSetPosition.GLOVES, 'armour', 'gloves'),
    buildRequiredOption(CraftSetPosition.FEET, 'armour', 'feet'),
    buildRequiredOption(CraftSetPosition.HELMET, 'armour', 'helmet'),
    buildRequiredOption(CraftSetPosition.RING_ONE, 'ring'),
    buildRequiredOption(CraftSetPosition.RING_TWO, 'ring'),
    buildRequiredOption(
      CraftSetPosition.DAMAGE_SPELL,
      'spell',
      undefined,
      'spell-damage'
    ),
    buildRequiredOption(
      CraftSetPosition.HEALING_SPELL,
      'spell',
      undefined,
      'spell-healing'
    ),
  ];

export const CRAFT_SET_REQUIRED_POSITIONS: CraftSetPosition[] =
  CRAFT_SET_REQUIRED_POSITION_OPTIONS.map((position) => position.key);

export const CRAFT_SET_ALL_POSITIONS: (
  | CraftSetHandPositionOption
  | CraftSetRequiredPositionOption
)[] = [
  ...CRAFT_SET_OPTIONAL_HAND_POSITIONS,
  ...CRAFT_SET_REQUIRED_POSITION_OPTIONS,
];
