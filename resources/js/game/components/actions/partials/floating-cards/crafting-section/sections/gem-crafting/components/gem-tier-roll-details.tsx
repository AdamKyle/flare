import React, { ReactNode } from 'react';

import { characterGemModifierLabels } from '../../../../../../../../reusable-components/character-gem/enums/character-gem-modifier-type';
import GemAbilityDetails from '../../../../../../../../reusable-components/character-gem/gem-ability-details';
import GemTierDefinition from '../api/definitions/gem-tier-definition';

import { formatNumberWithCommas } from 'game-utils/format-number';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import GeneralToolTip from 'ui/tool-tips/general-tool-tip';

interface GemTierRollDetailsProps {
  tier: GemTierDefinition;
  tier_number: number;
}

const formatRange = (
  minimum: number,
  maximum: number,
  percentage: boolean
): string => {
  const suffix = percentage ? '%' : '';

  if (minimum === maximum) {
    return `${formatNumberWithCommas(minimum)}${suffix}`;
  }

  return `${formatNumberWithCommas(minimum)}–${formatNumberWithCommas(maximum)}${suffix}`;
};

const formatModifierValue = (value: number, percentage: boolean): string =>
  `${formatNumberWithCommas(value)}${percentage ? '%' : ''}`;

const GemTierRollDetails = ({
  tier,
  tier_number: tierNumber,
}: GemTierRollDetailsProps): ReactNode => (
  <div className="space-y-6">
    <div>
      <h2 className="text-glacier-900 dark:text-glacier-100 text-xl font-semibold">
        Tier {tierNumber} possible rolls
      </h2>
      <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
        These are the abilities and modifier ranges used by the real Gem roll.
      </p>
    </div>

    {tier.possible_abilities.length > 0 ? (
      <section aria-labelledby="possible-gem-abilities" className="space-y-3">
        <h3
          id="possible-gem-abilities"
          className="text-mango-tango-600 dark:text-mango-tango-300 text-sm font-semibold"
        >
          Possible Gem Abilities
        </h3>
        {tier.possible_abilities.map((ability) => (
          <GemAbilityDetails key={ability.id} ability={ability} />
        ))}
      </section>
    ) : null}

    <section aria-labelledby="possible-gem-modifiers" className="space-y-3">
      <h3
        id="possible-gem-modifiers"
        className="text-mango-tango-600 dark:text-mango-tango-300 text-sm font-semibold"
      >
        Possible Modifiers
      </h3>
      <Dl>
        {tier.possible_modifiers.map((modifier) => (
          <React.Fragment key={modifier.modifier_type}>
            <Dt>
              <GeneralToolTip
                label={characterGemModifierLabels[modifier.modifier_type]}
                message={`This Gem can roll ${characterGemModifierLabels[modifier.modifier_type]} between ${formatModifierValue(modifier.minimum, modifier.percentage)} and ${formatModifierValue(modifier.maximum, modifier.percentage)} at Tier ${tierNumber}. The final value is randomly selected when the Gem is successfully crafted.`}
                placement="auto"
              />
              {characterGemModifierLabels[modifier.modifier_type]}
            </Dt>
            <Dd text_class="font-semibold text-emerald-700 dark:text-emerald-400 tabular-nums">
              <span className="inline-flex items-center gap-2">
                <i
                  className="fas fa-chevron-up text-emerald-600 dark:text-emerald-400"
                  aria-hidden="true"
                />
                {formatRange(
                  modifier.minimum,
                  modifier.maximum,
                  modifier.percentage
                )}
              </span>
            </Dd>
          </React.Fragment>
        ))}
      </Dl>
    </section>
  </div>
);

export default GemTierRollDetails;
