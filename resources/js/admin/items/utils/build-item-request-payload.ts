import ItemFormStateDefinition from '../definitions/item-form-state-definition';
import { isItemCatalogType } from '../enums/item-catalog-type';
import ItemRequestDefinition from '../api/definitions/item-request-definition';

const toNullableNumber = (value: string): number | null => {
  if (value.trim() === '') {
    return null;
  }

  const parsedValue = Number(value);

  return Number.isFinite(parsedValue) ? parsedValue : null;
};

const toNullableString = (value: string): string | null =>
  value.trim() === '' ? null : value;

export const buildItemRequestPayload = (
  state: ItemFormStateDefinition
): ItemRequestDefinition => {
  if (!isItemCatalogType(state.type)) {
    throw new Error('Item type is required.');
  }

  return {
    name: state.name,
    type: state.type,
    description: state.description,
    default_position:
      state.default_position === '' ? null : state.default_position,
    market_sellable: state.market_sellable,
    can_drop: state.can_drop,
    cost: toNullableNumber(state.cost),
    gold_dust_cost: toNullableNumber(state.gold_dust_cost),
    shards_cost: toNullableNumber(state.shards_cost),
    copper_coin_cost: toNullableNumber(state.copper_coin_cost),
    gold_bars_cost: toNullableNumber(state.gold_bars_cost),
    alchemy_type: state.alchemy_type === '' ? null : state.alchemy_type,
    specialty_type: state.specialty_type === '' ? null : state.specialty_type,
    base_damage: toNullableNumber(state.base_damage),
    base_ac: toNullableNumber(state.base_ac),
    base_healing: toNullableNumber(state.base_healing),
    base_damage_mod: toNullableNumber(state.base_damage_mod),
    base_ac_mod: toNullableNumber(state.base_ac_mod),
    base_healing_mod: toNullableNumber(state.base_healing_mod),
    str_mod: toNullableNumber(state.str_mod),
    dur_mod: toNullableNumber(state.dur_mod),
    dex_mod: toNullableNumber(state.dex_mod),
    chr_mod: toNullableNumber(state.chr_mod),
    int_mod: toNullableNumber(state.int_mod),
    agi_mod: toNullableNumber(state.agi_mod),
    focus_mod: toNullableNumber(state.focus_mod),
    ambush_chance: toNullableNumber(state.ambush_chance),
    ambush_resistance: toNullableNumber(state.ambush_resistance),
    counter_chance: toNullableNumber(state.counter_chance),
    counter_resistance: toNullableNumber(state.counter_resistance),
    effect: state.effect === '' ? null : state.effect,
    drop_location_id: state.drop_location_id,
    unlocks_class_id: state.unlocks_class_id,
    item_skill_id: state.item_skill_id,
    skill_name: toNullableString(state.skill_name),
    skill_bonus: toNullableNumber(state.skill_bonus),
    skill_training_bonus: toNullableNumber(state.skill_training_bonus),
    fight_time_out_mod_bonus: toNullableNumber(state.fight_time_out_mod_bonus),
    move_time_out_mod_bonus: toNullableNumber(state.move_time_out_mod_bonus),
    xp_bonus: toNullableNumber(state.xp_bonus),
    ignores_caps: state.ignores_caps,
    can_resurrect: state.can_resurrect,
    resurrection_chance: toNullableNumber(state.resurrection_chance),
    spell_evasion: toNullableNumber(state.spell_evasion),
    artifact_annulment: toNullableNumber(state.artifact_annulment),
    healing_reduction: toNullableNumber(state.healing_reduction),
    affix_damage_reduction: toNullableNumber(state.affix_damage_reduction),
    devouring_light: toNullableNumber(state.devouring_light),
    devouring_darkness: toNullableNumber(state.devouring_darkness),
    can_craft: state.can_craft,
    craft_only: state.craft_only,
    crafting_type: state.crafting_type === '' ? null : state.crafting_type,
    skill_level_required: toNullableNumber(state.skill_level_required),
    skill_level_trivial: toNullableNumber(state.skill_level_trivial),
    usable: state.usable,
    can_stack: state.can_stack,
    lasts_for: toNullableNumber(state.lasts_for),
    stat_increase: state.stat_increase,
    increase_stat_by: toNullableNumber(state.increase_stat_by),
    damages_kingdoms: state.damages_kingdoms,
    kingdom_damage: toNullableNumber(state.kingdom_damage),
    affects_skill_type: state.affects_skill_type,
    increase_skill_bonus_by: toNullableNumber(state.increase_skill_bonus_by),
    increase_skill_training_bonus_by: toNullableNumber(
      state.increase_skill_training_bonus_by
    ),
    can_use_on_other_items: state.can_use_on_other_items,
    holy_level: toNullableNumber(state.holy_level),
    gains_additional_level: state.gains_additional_level,
  };
};
