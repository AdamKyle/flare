import DuplicateGemAbilityDefinition, {
  DuplicateGemAbilityItemDefinition,
} from '../../../api-definitions/gems/duplicate-gem-ability-definition';
import { DuplicateGemAbilitySource } from '../enums/duplicate-gem-ability-source';
import PreparedDuplicateGemAbility, {
  DuplicateGemAbilitySetGroup,
} from '../types/prepared-duplicate-gem-ability';

const groupItemsBySet = (
  items: DuplicateGemAbilityItemDefinition[]
): DuplicateGemAbilitySetGroup[] => {
  const groupsBySetId = new Map<number | null, DuplicateGemAbilitySetGroup>();

  items
    .filter((item) => item.source === DuplicateGemAbilitySource.SET)
    .forEach((item) => {
      const group = groupsBySetId.get(item.set_id) ?? {
        set_id: item.set_id,
        set_name: item.set_name,
        items: [],
      };

      groupsBySetId.set(item.set_id, {
        ...group,
        items: [...group.items, item],
      });
    });

  return Array.from(groupsBySetId.values());
};

export const prepareDuplicateGemAbilities = (
  duplicates: DuplicateGemAbilityDefinition[]
): PreparedDuplicateGemAbility[] =>
  duplicates.map((duplicate) => ({
    ability: duplicate.ability,
    equipped_items: duplicate.items.filter(
      (item) => item.source === DuplicateGemAbilitySource.EQUIPPED
    ),
    inventory_items: duplicate.items.filter(
      (item) => item.source === DuplicateGemAbilitySource.INVENTORY
    ),
    set_groups: groupItemsBySet(duplicate.items),
  }));
