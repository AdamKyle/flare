import React, { ReactNode } from 'react';

import { CharacterGemModifierType } from './enums/character-gem-modifier-type';
import CharacterGemCompactSummaryProps from './types/character-gem-compact-summary-props';
import { formatCharacterGemModifier } from './utils/format-character-gem-modifier';
import {
  gemAbilityTypeHelp,
  gemAbilityTypeLabel,
} from '../gem-ability/enums/gem-ability-type';

import GeneralToolTip from 'ui/tool-tips/general-tool-tip';

const CharacterGemCompactSummary = ({
  modifiers,
}: CharacterGemCompactSummaryProps): ReactNode => {
  const ability = modifiers.find(
    (modifier) =>
      modifier.modifier_type === CharacterGemModifierType.GEM_ABILITY &&
      modifier.ability !== null
  )?.ability;
  const modifierSummary = modifiers
    .filter(
      (modifier) =>
        modifier.modifier_type !== CharacterGemModifierType.GEM_ABILITY
    )
    .map(formatCharacterGemModifier)
    .filter((modifier) => modifier !== null)
    .map((modifier) => `${modifier.label}: ${modifier.value}`)
    .join(', ');

  const renderAbility = (): ReactNode => {
    if (!ability) {
      return null;
    }

    const typeLabel = gemAbilityTypeLabel(ability.ability_type);

    return (
      <p className="flex flex-wrap items-center gap-x-1 text-sm">
        <span className="font-medium">{ability.name}</span>
        <span aria-hidden="true">—</span>
        <span>{typeLabel}</span>
        <span className="pointer-events-auto inline-flex">
          <GeneralToolTip
            label={`${typeLabel} Gem ability`}
            message={gemAbilityTypeHelp(ability.ability_type)}
            placement="auto"
            trigger={<i className="fas fa-info-circle" aria-hidden="true" />}
          />
        </span>
      </p>
    );
  };

  return (
    <div className="space-y-1">
      {renderAbility()}
      {modifierSummary ? (
        <p className="text-sm leading-relaxed">{modifierSummary}</p>
      ) : null}
    </div>
  );
};

export default CharacterGemCompactSummary;
