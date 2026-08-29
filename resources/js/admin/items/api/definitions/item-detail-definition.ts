import AdminQuestItemPresentationDefinition from './admin-quest-item-presentation-definition';
import AdminUsableItemPresentationDefinition from './admin-usable-item-presentation-definition';
import { ItemRelatedIdentityDefinition } from './item-definition';
import { ItemAlchemyType } from '../../enums/item-alchemy-type';
import { ItemCatalogType } from '../../enums/item-catalog-type';
import { ItemCraftingType } from '../../enums/item-crafting-type';
import { ItemDefaultPosition } from '../../enums/item-default-position';
import { ItemSpecialtyType } from '../../enums/item-specialty-type';

export interface ItemManagementDefinition {
  can_craft: boolean;
  craft_only: boolean;
  crafting_type: ItemCraftingType | null;
  market_sellable: boolean;
  can_drop: boolean;
  default_position: ItemDefaultPosition | null;
  specialty_type: ItemSpecialtyType | null;
  alchemy_type: ItemAlchemyType | null;
  skill_level_required: number | null;
  skill_level_trivial: number | null;
  unlocks_class: ItemRelatedIdentityDefinition | null;
  item_skill: ItemRelatedIdentityDefinition | null;
  is_generated_variant: boolean;
}

interface ItemDetailBaseDefinition {
  id: number;
  name: string;
  type: ItemCatalogType;
  management: ItemManagementDefinition;
}

export interface ItemEquippablePresentationDefinition {
  presentation_kind: 'equippable';
  presentation: Record<string, never>;
}

export interface ItemQuestPresentationDefinition {
  presentation_kind: 'quest';
  presentation: AdminQuestItemPresentationDefinition;
}

export interface ItemUsablePresentationDefinition {
  presentation_kind: 'usable';
  presentation: AdminUsableItemPresentationDefinition;
}

export type ItemPresentationKind =
  | ItemEquippablePresentationDefinition
  | ItemQuestPresentationDefinition
  | ItemUsablePresentationDefinition;

/**
 * `presentation_kind` and `presentation` form a discriminated union so React
 * can narrow `item.presentation` to the correct shape by checking
 * `presentation_kind` alone, without a forced type assertion.
 */
type ItemDetailDefinition = ItemDetailBaseDefinition & ItemPresentationKind;

export default ItemDetailDefinition;
