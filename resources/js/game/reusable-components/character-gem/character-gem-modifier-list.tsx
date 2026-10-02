import React, { ReactNode } from 'react';

import {
  CharacterGemModifierType,
  rawStatCharacterGemModifierTypes,
} from './enums/character-gem-modifier-type';
import GemAbilityDetails from './gem-ability-details';
import CharacterGemModifierListProps from './types/character-gem-modifier-list-props';
import { formatCharacterGemModifier } from './utils/format-character-gem-modifier';
import CharacterGemModifierDefinition from '../../api-definitions/gems/character-gem-modifier-definition';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import GeneralToolTip from 'ui/tool-tips/general-tool-tip';

const CharacterGemModifierList = ({
  modifiers,
}: CharacterGemModifierListProps): ReactNode => {
  const abilityRows = modifiers.flatMap((modifier) =>
    modifier.modifier_type === CharacterGemModifierType.GEM_ABILITY &&
    modifier.ability !== null
      ? [{ roll_position: modifier.roll_position, ability: modifier.ability }]
      : []
  );

  const modifierRows = modifiers.flatMap((modifier) => {
    if (modifier.modifier_type === CharacterGemModifierType.GEM_ABILITY) {
      return [];
    }

    const formattedModifier = formatCharacterGemModifier(modifier);

    return formattedModifier ? [{ modifier, ...formattedModifier }] : [];
  });

  const renderModifierLabel = (
    modifier: CharacterGemModifierDefinition,
    label: string,
    value: string
  ): ReactNode => {
    if (!rawStatCharacterGemModifierTypes.has(modifier.modifier_type)) {
      return label;
    }

    return (
      <>
        <GeneralToolTip
          label={label}
          message={`When this gem is socketed into equipped gear, it adds ${value} directly to your ${label} before equipment, boon, skill, class, and map-reduction modifiers are applied. Its contribution appears in the ${label} breakdown under Modified Stats.`}
          placement="auto"
        />
        {label}
      </>
    );
  };

  const renderAbilities = (): ReactNode => {
    if (abilityRows.length === 0) {
      return null;
    }

    return (
      <div className="space-y-4">
        <h3 className="text-mango-tango-600 dark:text-mango-tango-300 text-xs font-semibold tracking-wide uppercase">
          Ability
        </h3>
        {abilityRows.map((abilityRow) => (
          <GemAbilityDetails
            key={abilityRow.roll_position}
            ability={abilityRow.ability}
          />
        ))}
      </div>
    );
  };

  const renderModifiers = (): ReactNode => {
    if (modifierRows.length === 0) {
      return null;
    }

    return (
      <div className="space-y-2">
        <h3 className="text-mango-tango-600 dark:text-mango-tango-300 text-xs font-semibold tracking-wide uppercase">
          Modifiers
        </h3>
        <Dl>
          {modifierRows.map((modifierRow) => (
            <React.Fragment key={modifierRow.modifier.roll_position}>
              <Dt>
                {renderModifierLabel(
                  modifierRow.modifier,
                  modifierRow.label,
                  modifierRow.value
                )}
              </Dt>
              <Dd text_class="font-semibold text-emerald-700 dark:text-emerald-400">
                {modifierRow.value}
              </Dd>
            </React.Fragment>
          ))}
        </Dl>
      </div>
    );
  };

  return (
    <div className="space-y-5">
      {renderAbilities()}
      {renderModifiers()}
    </div>
  );
};

export default CharacterGemModifierList;
