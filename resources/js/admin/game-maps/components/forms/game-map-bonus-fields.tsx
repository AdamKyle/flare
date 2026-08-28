import React, { ReactNode } from 'react';

import GameMapBonusFieldsProps from '../../types/game-map-bonus-fields-props';

import NumberField from 'ui/forms/number-field';

const GameMapBonusFields = ({
  state,
  errors,
  on_change: onChange,
}: GameMapBonusFieldsProps): ReactNode => {
  return (
    <div className="space-y-4">
      <NumberField
        id="game-map-xp-bonus"
        label="XP Bonus (%)"
        value={state.xp_bonus}
        on_change={(value) => onChange('xp_bonus', value)}
        error={errors.xp_bonus}
        required
      />

      <NumberField
        id="game-map-skill-training-bonus"
        label="Skill Training Bonus (%)"
        value={state.skill_training_bonus}
        on_change={(value) => onChange('skill_training_bonus', value)}
        error={errors.skill_training_bonus}
        required
      />

      <NumberField
        id="game-map-drop-chance-bonus"
        label="Drop Chance Bonus (%)"
        value={state.drop_chance_bonus}
        on_change={(value) => onChange('drop_chance_bonus', value)}
        error={errors.drop_chance_bonus}
        required
      />

      <NumberField
        id="game-map-enemy-stat-bonus"
        label="Enemy Stat Increase (%)"
        value={state.enemy_stat_bonus}
        on_change={(value) => onChange('enemy_stat_bonus', value)}
        error={errors.enemy_stat_bonus}
        required
      />

      <NumberField
        id="game-map-character-attack-reduction"
        label="Character Attack Reduction (%)"
        value={state.character_attack_reduction}
        on_change={(value) => onChange('character_attack_reduction', value)}
        error={errors.character_attack_reduction}
        required
      />
    </div>
  );
};

export default GameMapBonusFields;
