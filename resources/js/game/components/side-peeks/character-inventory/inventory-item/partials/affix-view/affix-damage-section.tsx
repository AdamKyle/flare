import React from 'react';

import DefinitionRow from '../../../../../../reusable-components/viewable-sections/definition-row';
import InfoLabel from '../../../../../../reusable-components/viewable-sections/info-label';
import Section from '../../../../../../reusable-components/viewable-sections/section';
import StatRowPercent from '../../../../../../reusable-components/viewable-sections/stat-row-percent';
import { formatPercent } from '../../../../../../util/format-number';
import AffixDamageSectionProps from '../../types/partials/affix-view/affix-damage-section-props';

const AffixDamageSection = ({ affix }: AffixDamageSectionProps) => {
  const damageAmount = Number(affix.damage_amount ?? 0);

  if (damageAmount <= 0) {
    return null;
  }

  const damageTooltip = `When you attack an enemy with any attack option, we use ${formatPercent(
    damageAmount
  )} of your weapon damage to deal additional damage. This is known as affix damage.`;

  const stackingTooltip = affix.damage_can_stack
    ? 'The damage value above stacks additively with other affixes that also provide stackable damage, increasing the percent of your weapon damage applied. This can exceed 100%.'
    : 'This damage does not stack with other affixes that provide damage. We take the highest value among your non-stacking damage affixes.';

  const irresistibleTooltip = affix.irresistible_damage
    ? 'This damage is irresistible; the enemy cannot resist it.'
    : 'This damage is resistible; the enemy can resist it.';

  const stackingText = affix.damage_can_stack ? 'Stacks' : 'Does Not Stack';

  const irresistibleText = affix.irresistible_damage ? 'Yes' : 'No';

  return (
    <Section title="Affix Damage">
      <StatRowPercent
        label="Affix Damage"
        value={damageAmount}
        tooltip={damageTooltip}
      />

      <DefinitionRow
        left={
          <InfoLabel
            label="Damage Stacking"
            tooltip={stackingTooltip}
            tooltipValue={0}
            tooltipAlign="right"
            tooltipRenderAsPercent={false}
            tooltipSize="sm"
          />
        }
        right={
          <span className="font-semibold text-gray-700 dark:text-gray-200">
            {stackingText}
          </span>
        }
      />

      <DefinitionRow
        left={
          <InfoLabel
            label="Irresistible"
            tooltip={irresistibleTooltip}
            tooltipValue={0}
            tooltipAlign="right"
            tooltipRenderAsPercent={false}
            tooltipSize="sm"
          />
        }
        right={
          <span className="font-semibold text-gray-700 dark:text-gray-200">
            {irresistibleText}
          </span>
        }
      />
    </Section>
  );
};

export default AffixDamageSection;
