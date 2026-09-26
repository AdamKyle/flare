import { BaseItemDetails } from '../../../../api-definitions/items/base-item-details';
import SkillItemContributionDefinition from '../api/definitions/skill-item-contribution-definition';

/**
 * The canonical backpack item styles read `BaseItemDetails`; contributing
 * items only carry the rarity fields those styles need, so the remaining
 * fields take their neutral values.
 */
export const buildSkillItemStyleShape = (
  item: SkillItemContributionDefinition
): BaseItemDetails => ({
  affix_count: item.affix_count,
  max_holy_stacks: 0,
  holy_stacks_applied: item.holy_stacks_applied,
  holy_stacks_total_stat_increase: 0,
  is_cosmic: item.is_cosmic,
  is_mythic: item.is_mythic,
  is_unique: item.is_unique,
  usable: false,
  holy_level: null,
  damages_kingdoms: false,
  name: item.name,
  description: '',
  type: item.type,
  cost: 0,
  item_id: item.item_id,
  slot_id: item.slot_id,
});
