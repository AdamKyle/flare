import { useEffect, useState } from 'react';

import { focusAndScrollToField } from '../../../utils/focus-and-scroll-to-field';
import LocationGemFormErrorsDefinition from '../definitions/location-gem-form-errors-definition';

const STEP_FIELDS = [
  [
    { field: 'location_id', id: 'location-gem-location' },
    { field: 'name', id: 'location-gem-name' },
    { field: 'description', id: 'location-gem-description' },
  ],
  [
    {
      field: 'character_xp_bonus_range',
      id: 'location-gem-character-xp-bonus-range',
    },
    {
      field: 'character_class_rank_xp_bonus_range',
      id: 'location-gem-character-class-rank-xp-bonus-range',
    },
    {
      field: 'kingdom_passive_training_reduction_range',
      id: 'location-gem-kingdom-passive-training-reduction-range',
    },
    {
      field: 'character_class_specialty_xp_gain_range',
      id: 'location-gem-character-class-specialty-xp-gain-range',
    },
    { field: 'crafting_skill_ids', id: 'location-gem-crafting-skills' },
    {
      field: 'crafting_skill_bonus_range',
      id: 'location-gem-crafting-skill-bonus-range',
    },
  ],
  [
    { field: 'gold_gain_range', id: 'location-gem-gold-gain-range' },
    { field: 'gold_dust_gain_range', id: 'location-gem-gold-dust-gain-range' },
    { field: 'shards_gain_range', id: 'location-gem-shards-gain-range' },
    {
      field: 'copper_coin_gain_range',
      id: 'location-gem-copper-coin-gain-range',
    },
    {
      field: 'item_drop_chance_increase_range',
      id: 'location-gem-item-drop-chance-increase-range',
    },
    {
      field: 'unique_item_drop_chance_increase_range',
      id: 'location-gem-unique-item-drop-chance-increase-range',
    },
    {
      field: 'mythic_item_drop_chance_increase_range',
      id: 'location-gem-mythic-item-drop-chance-increase-range',
    },
    {
      field: 'cosmic_item_drop_chance_increase_range',
      id: 'location-gem-cosmic-item-drop-chance-increase-range',
    },
  ],
  [
    {
      field: 'enemy_strength_increase_range',
      id: 'location-gem-enemy-strength-increase-range',
    },
    {
      field: 'enemy_healing_increase_range',
      id: 'location-gem-enemy-healing-increase-range',
    },
    {
      field: 'enemy_spell_evasion_range',
      id: 'location-gem-enemy-spell-evasion-range',
    },
    {
      field: 'enemy_affix_resistance_range',
      id: 'location-gem-enemy-affix-resistance-range',
    },
    {
      field: 'enemy_entrancing_chance_range',
      id: 'location-gem-enemy-entrancing-chance-range',
    },
    {
      field: 'enemy_devouring_light_chance_range',
      id: 'location-gem-enemy-devouring-light-chance-range',
    },
    {
      field: 'enemy_devouring_darkness_chance_range',
      id: 'location-gem-enemy-devouring-darkness-chance-range',
    },
    {
      field: 'enemy_ambush_chance_range',
      id: 'location-gem-enemy-ambush-chance-range',
    },
    {
      field: 'enemy_ambush_resistance_range',
      id: 'location-gem-enemy-ambush-resistance-range',
    },
    {
      field: 'enemy_counter_chance_range',
      id: 'location-gem-enemy-counter-chance-range',
    },
    {
      field: 'enemy_counter_resistance_range',
      id: 'location-gem-enemy-counter-resistance-range',
    },
  ],
  [
    {
      field: 'enemy_quest_item_drop_chance_increase_range',
      id: 'location-gem-enemy-quest-item-drop-chance-increase-range',
    },
    {
      field: 'monster_xp_increase_range',
      id: 'location-gem-monster-xp-increase-range',
    },
    {
      field: 'monster_gold_drop_increase_range',
      id: 'location-gem-monster-gold-drop-increase-range',
    },
    { field: 'monster_atonement', id: 'location-gem-monster-atonement' },
    {
      field: 'monster_atonement_range',
      id: 'location-gem-monster-atonement-range',
    },
  ],
] as const;

export const useFocusFirstInvalidLocationGemField = (
  errors: LocationGemFormErrorsDefinition,
  stepIndex: number,
  goToStep: (stepIndex: number) => void
): (() => void) => {
  const [attempt, setAttempt] = useState(0);

  useEffect(() => {
    if (attempt === 0) return;

    const stepIndexWithError = STEP_FIELDS.findIndex((fields) =>
      fields.some((candidate) => candidate.field in errors)
    );

    if (stepIndexWithError === -1) return;

    if (stepIndexWithError !== stepIndex) {
      goToStep(stepIndexWithError);

      return;
    }

    const field = STEP_FIELDS[stepIndexWithError]?.find(
      (candidate) => candidate.field in errors
    );
    if (field) focusAndScrollToField(field.id);
  }, [attempt, errors, stepIndex, goToStep]);

  return () => setAttempt((value) => value + 1);
};
