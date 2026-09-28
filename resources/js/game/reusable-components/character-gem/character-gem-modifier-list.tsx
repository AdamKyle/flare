import React, { ReactNode } from 'react';

import {
  CharacterGemModifierType,
  characterGemModifierLabels,
  integerCharacterGemModifierTypes,
} from './enums/character-gem-modifier-type';
import CharacterGemModifierListProps from './types/character-gem-modifier-list-props';
import CharacterGemModifierDefinition from '../../api-definitions/gems/character-gem-modifier-definition';
import { gemAbilityEffectTypeLabel } from '../gem-ability/enums/gem-ability-effect-type';
import { gemAbilityScalingSourceLabel } from '../gem-ability/enums/gem-ability-scaling-source';
import { gemAbilityTypeLabel } from '../gem-ability/enums/gem-ability-type';

const CharacterGemModifierList = ({
  modifiers,
}: CharacterGemModifierListProps): ReactNode => {
  const renderAbility = (
    modifier: CharacterGemModifierDefinition
  ): ReactNode => {
    if (modifier.ability === null) {
      return null;
    }

    const ability = modifier.ability;

    return (
      <>
        <strong>{ability.name}</strong> —{' '}
        {gemAbilityTypeLabel(ability.ability_type)}
        <p>{ability.description}</p>
        <p>Allowed actions: {ability.attack_types.join(', ')}</p>
        <p>Effect: {gemAbilityEffectTypeLabel(ability.effect_type)}</p>
        {ability.proc_chance !== null ? (
          <p>Proc chance: {(ability.proc_chance * 100).toFixed(2)}%</p>
        ) : null}
        <p>Effect value: {(ability.effect_value * 100).toFixed(2)}%</p>
        {ability.scaling_source !== null ? (
          <p>
            Scaling source:{' '}
            {gemAbilityScalingSourceLabel(ability.scaling_source)}
          </p>
        ) : null}
      </>
    );
  };

  const renderModifier = (
    modifier: CharacterGemModifierDefinition
  ): ReactNode => {
    if (modifier.modifier_type === CharacterGemModifierType.GEM_ABILITY) {
      return renderAbility(modifier);
    }

    const label = characterGemModifierLabels[modifier.modifier_type];

    if (modifier.amount === null) {
      return label;
    }

    if (!integerCharacterGemModifierTypes.has(modifier.modifier_type)) {
      return `${label}: +${(modifier.amount * 100).toFixed(2)}%`;
    }

    return `${label}: +${modifier.amount}`;
  };

  return (
    <ul className="mt-2 list-inside list-disc space-y-2">
      {modifiers.map((modifier) => (
        <li key={modifier.roll_position}>{renderModifier(modifier)}</li>
      ))}
    </ul>
  );
};

export default CharacterGemModifierList;
