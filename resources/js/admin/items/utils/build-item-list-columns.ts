import ItemDefinition from '../api/definitions/item-definition';
import { ITEM_ALCHEMY_TYPE_LABELS } from '../enums/item-alchemy-type';
import { ITEM_CATALOG_TYPE_LABELS } from '../enums/item-catalog-type';
import { ITEM_EFFECT_TYPE_LABELS } from '../enums/item-effect-type';
import { ItemProfile } from '../enums/item-profile';
import { ITEM_SPECIALTY_TYPE_LABELS } from '../enums/item-specialty-type';

import DataTableColumnDefinition from 'ui/data-table/types/data-table-column-definition';

const nameColumn: DataTableColumnDefinition<ItemDefinition> = {
  key: 'name',
  header: 'Name',
  sortable: true,
  sort_key: 'name',
  value: (row) => row.name,
};

const yesNo = (value: boolean): string => (value ? 'Yes' : 'No');

export const buildItemListColumns = (
  profile: ItemProfile
): DataTableColumnDefinition<ItemDefinition>[] => {
  switch (profile) {
    case ItemProfile.WEAPONS:
      return [
        nameColumn,
        {
          key: 'type',
          header: 'Type',
          value: (row) => ITEM_CATALOG_TYPE_LABELS[row.type],
        },
        {
          key: 'base_damage',
          header: 'Base Damage',
          sortable: true,
          sort_key: 'base_damage',
          value: (row) => row.base_damage ?? '—',
        },
        {
          key: 'cost',
          header: 'Gold Cost',
          sortable: true,
          sort_key: 'cost',
          value: (row) => row.cost ?? '—',
        },
        {
          key: 'skill_level_required',
          header: 'Minimum Crafting Level',
          sortable: true,
          sort_key: 'skill_level_required',
          value: (row) => row.skill_level_required ?? '—',
        },
      ];
    case ItemProfile.ARMOUR:
      return [
        nameColumn,
        {
          key: 'type',
          header: 'Type',
          value: (row) => ITEM_CATALOG_TYPE_LABELS[row.type],
        },
        {
          key: 'base_ac',
          header: 'Base AC',
          sortable: true,
          sort_key: 'base_ac',
          value: (row) => row.base_ac ?? '—',
        },
        {
          key: 'cost',
          header: 'Gold Cost',
          sortable: true,
          sort_key: 'cost',
          value: (row) => row.cost ?? '—',
        },
        {
          key: 'skill_level_required',
          header: 'Minimum Crafting Level',
          sortable: true,
          sort_key: 'skill_level_required',
          value: (row) => row.skill_level_required ?? '—',
        },
      ];
    case ItemProfile.DAMAGE_SPELLS:
      return [
        nameColumn,
        {
          key: 'base_damage',
          header: 'Base Damage',
          sortable: true,
          sort_key: 'base_damage',
          value: (row) => row.base_damage ?? '—',
        },
        {
          key: 'cost',
          header: 'Gold Cost',
          sortable: true,
          sort_key: 'cost',
          value: (row) => row.cost ?? '—',
        },
        {
          key: 'skill_level_required',
          header: 'Minimum Crafting Level',
          sortable: true,
          sort_key: 'skill_level_required',
          value: (row) => row.skill_level_required ?? '—',
        },
        {
          key: 'skill_level_trivial',
          header: 'Trivial Crafting Level',
          sortable: true,
          sort_key: 'skill_level_trivial',
          value: (row) => row.skill_level_trivial ?? '—',
        },
      ];
    case ItemProfile.HEALING_SPELLS:
      return [
        nameColumn,
        {
          key: 'base_healing',
          header: 'Base Healing',
          sortable: true,
          sort_key: 'base_healing',
          value: (row) => row.base_healing ?? '—',
        },
        {
          key: 'cost',
          header: 'Gold Cost',
          sortable: true,
          sort_key: 'cost',
          value: (row) => row.cost ?? '—',
        },
        {
          key: 'skill_level_required',
          header: 'Minimum Crafting Level',
          sortable: true,
          sort_key: 'skill_level_required',
          value: (row) => row.skill_level_required ?? '—',
        },
        {
          key: 'skill_level_trivial',
          header: 'Trivial Crafting Level',
          sortable: true,
          sort_key: 'skill_level_trivial',
          value: (row) => row.skill_level_trivial ?? '—',
        },
      ];
    case ItemProfile.RINGS:
      return [
        nameColumn,
        {
          key: 'base_damage_mod',
          header: 'Damage Modifier',
          sortable: true,
          sort_key: 'base_damage_mod',
          value: (row) => row.base_damage_mod ?? '—',
        },
        {
          key: 'base_ac_mod',
          header: 'AC Modifier',
          sortable: true,
          sort_key: 'base_ac_mod',
          value: (row) => row.base_ac_mod ?? '—',
        },
        {
          key: 'base_healing_mod',
          header: 'Healing Modifier',
          sortable: true,
          sort_key: 'base_healing_mod',
          value: (row) => row.base_healing_mod ?? '—',
        },
        {
          key: 'cost',
          header: 'Gold Cost',
          sortable: true,
          sort_key: 'cost',
          value: (row) => row.cost ?? '—',
        },
      ];
    case ItemProfile.TRINKETS:
      return [
        nameColumn,
        {
          key: 'ambush_chance',
          header: 'Ambush Chance',
          sortable: true,
          sort_key: 'ambush_chance',
          value: (row) => row.ambush_chance ?? '—',
        },
        {
          key: 'ambush_resistance',
          header: 'Ambush Resistance',
          sortable: true,
          sort_key: 'ambush_resistance',
          value: (row) => row.ambush_resistance ?? '—',
        },
        {
          key: 'counter_chance',
          header: 'Counter Chance',
          sortable: true,
          sort_key: 'counter_chance',
          value: (row) => row.counter_chance ?? '—',
        },
        {
          key: 'counter_resistance',
          header: 'Counter Resistance',
          sortable: true,
          sort_key: 'counter_resistance',
          value: (row) => row.counter_resistance ?? '—',
        },
      ];
    case ItemProfile.ARTIFACTS:
      return [
        nameColumn,
        {
          key: 'item_skill',
          header: 'Item Skill',
          sortable: true,
          sort_key: 'item_skill_id',
          value: (row) => row.item_skill?.name ?? '—',
        },
        {
          key: 'specialty_type',
          header: 'Specialty Type',
          sortable: true,
          sort_key: 'specialty_type',
          value: (row) =>
            row.specialty_type === null
              ? '—'
              : ITEM_SPECIALTY_TYPE_LABELS[row.specialty_type],
        },
        {
          key: 'cost',
          header: 'Gold Cost',
          sortable: true,
          sort_key: 'cost',
          value: (row) => row.cost ?? '—',
        },
        {
          key: 'can_craft',
          header: 'Craftable',
          sortable: true,
          sort_key: 'can_craft',
          value: (row) => yesNo(row.can_craft),
        },
      ];
    case ItemProfile.QUEST_ITEMS:
      return [
        nameColumn,
        {
          key: 'drop_location',
          header: 'Drop Location',
          sortable: true,
          sort_key: 'drop_location_id',
          value: (row) => row.drop_location?.name ?? '—',
        },
        {
          key: 'effect',
          header: 'Effect',
          sortable: true,
          sort_key: 'effect',
          value: (row) =>
            row.effect === null ? '—' : ITEM_EFFECT_TYPE_LABELS[row.effect],
        },
        {
          key: 'unlocks_class',
          header: 'Unlocks Class',
          sortable: true,
          sort_key: 'unlocks_class_id',
          value: (row) => row.unlocks_class?.name ?? '—',
        },
        {
          key: 'can_drop',
          header: 'Can Drop',
          sortable: true,
          sort_key: 'can_drop',
          value: (row) => yesNo(row.can_drop),
        },
      ];
    case ItemProfile.ALCHEMY:
      return [
        nameColumn,
        {
          key: 'alchemy_type',
          header: 'Alchemy Type',
          sortable: true,
          sort_key: 'alchemy_type',
          value: (row) =>
            row.alchemy_type === null
              ? '—'
              : ITEM_ALCHEMY_TYPE_LABELS[row.alchemy_type],
        },
        {
          key: 'gold_dust_cost',
          header: 'Gold Dust Cost',
          sortable: true,
          sort_key: 'gold_dust_cost',
          value: (row) => row.gold_dust_cost ?? '—',
        },
        {
          key: 'shards_cost',
          header: 'Shards Cost',
          sortable: true,
          sort_key: 'shards_cost',
          value: (row) => row.shards_cost ?? '—',
        },
        {
          key: 'skill_level_required',
          header: 'Minimum Crafting Level',
          sortable: true,
          sort_key: 'skill_level_required',
          value: (row) => row.skill_level_required ?? '—',
        },
      ];
    case ItemProfile.SPECIALTY:
      return [
        nameColumn,
        {
          key: 'specialty_type',
          header: 'Specialty Type',
          sortable: true,
          sort_key: 'specialty_type',
          value: (row) =>
            row.specialty_type === null
              ? '—'
              : ITEM_SPECIALTY_TYPE_LABELS[row.specialty_type],
        },
        {
          key: 'type',
          header: 'Item Type',
          value: (row) => ITEM_CATALOG_TYPE_LABELS[row.type],
        },
        {
          key: 'cost',
          header: 'Gold Cost',
          sortable: true,
          sort_key: 'cost',
          value: (row) => row.cost ?? '—',
        },
        {
          key: 'gold_bars_cost',
          header: 'Gold Bars Cost',
          sortable: true,
          sort_key: 'gold_bars_cost',
          value: (row) => row.gold_bars_cost ?? '—',
        },
      ];
    case ItemProfile.ALL:
    default:
      return [
        nameColumn,
        {
          key: 'type',
          header: 'Type',
          value: (row) => ITEM_CATALOG_TYPE_LABELS[row.type],
        },
        {
          key: 'can_craft',
          header: 'Craftable',
          sortable: true,
          sort_key: 'can_craft',
          value: (row) => yesNo(row.can_craft),
        },
        {
          key: 'usable',
          header: 'Usable',
          sortable: true,
          sort_key: 'usable',
          value: (row) => yesNo(row.usable),
        },
        {
          key: 'market_sellable',
          header: 'Market Sellable',
          sortable: true,
          sort_key: 'market_sellable',
          value: (row) => yesNo(row.market_sellable),
        },
      ];
  }
};
