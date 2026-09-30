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
  const renderModifier = (
    modifier: CharacterGemModifierDefinition
  ): { label: string; value: string } | null => {
    if (modifier.modifier_type === CharacterGemModifierType.GEM_ABILITY) {
      return null;
    }

    return formatCharacterGemModifier(modifier);
  };

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

  const abilities = modifiers.filter(
    (modifier) =>
      modifier.modifier_type === CharacterGemModifierType.GEM_ABILITY
  );
  const factualModifiers = modifiers.filter(
    (modifier) =>
      modifier.modifier_type !== CharacterGemModifierType.GEM_ABILITY
  );

  return (
    <div className="space-y-5">
      {abilities.length > 0 ? (
        <div className="space-y-4">
          <h3 className="text-mango-tango-600 dark:text-mango-tango-300 text-xs font-semibold tracking-wide uppercase">
            Ability
          </h3>
          {abilities.map((modifier) => (
            <div key={modifier.roll_position}>
              {modifier.ability !== null ? (
                <GemAbilityDetails ability={modifier.ability} />
              ) : null}
            </div>
          ))}
        </div>
      ) : null}
      {factualModifiers.length > 0 ? (
        <div className="space-y-2">
          <h3 className="text-mango-tango-600 dark:text-mango-tango-300 text-xs font-semibold tracking-wide uppercase">
            Modifiers
          </h3>
          <Dl>
            {factualModifiers.map((modifier) => {
              const row = renderModifier(modifier);

              if (!row) {
                return null;
              }

              return (
                <React.Fragment key={modifier.roll_position}>
                  <Dt>{renderModifierLabel(modifier, row.label, row.value)}</Dt>
                  <Dd text_class="font-semibold text-emerald-700 dark:text-emerald-400">
                    {row.value}
                  </Dd>
                </React.Fragment>
              );
            })}
          </Dl>
        </div>
      ) : null}
    </div>
  );
};

export default CharacterGemModifierList;
