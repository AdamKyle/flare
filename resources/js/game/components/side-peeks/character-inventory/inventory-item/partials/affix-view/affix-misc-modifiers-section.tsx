import React from 'react';

import Section from '../../../../../../reusable-components/viewable-sections/section';
import StatRowPercent from '../../../../../../reusable-components/viewable-sections/stat-row-percent';
import { formatPercent } from '../../../../../../util/format-number';
import AffixMiscModifiersSectionProps from '../../types/partials/affix-view/affix-misc-modifiers-section';

const AffixMiscModifiersSection = ({
  affix,
}: AffixMiscModifiersSectionProps) => {
  const lifeStealAmount = Number(affix.steal_life_amount ?? 0);
  const devouringLight = Number(affix.devouring_light ?? 0);
  const entrancedChance = Number(affix.entranced_chance ?? 0);
  const skillReduction = Number(affix.skill_reduction ?? 0);
  const resistanceReduction = Number(affix.resistance_reduction ?? 0);

  const allZero =
    lifeStealAmount <= 0 &&
    devouringLight <= 0 &&
    entrancedChance <= 0 &&
    skillReduction <= 0 &&
    resistanceReduction <= 0;

  if (allZero) {
    return null;
  }

  const lifeStealTooltip = `This steals ${formatPercent(
    lifeStealAmount
  )} of the enemy's health during your attack phase. Stacks additively across items up to 50% for non-vampire classes and up to 99% for Vampires.`;

  const devouringLightTooltip =
    "This stacks additively and reduces the enemy's chance to void you, which would render you weak and vulnerable. (If an enemy voids you, all enchantments on your gear fail.)";

  const entrancingTooltip =
    'This stacks additively and increases the chance to entrance an enemy, preventing them from attacking you, ambushing, or countering you. This does not prevent their ability to void or devoid you.';

  const renderPositiveRow = (label: string, tooltip: string, value: number) => {
    if (value <= 0) {
      return null;
    }

    return <StatRowPercent label={label} value={value} tooltip={tooltip} />;
  };

  const renderNegativeRow = (label: string, tooltip: string, value: number) => {
    if (value <= 0) {
      return null;
    }

    return (
      <StatRowPercent label={label} value={value} tooltip={tooltip} negative />
    );
  };

  return (
    <Section title="Misc. Modifiers">
      {renderPositiveRow('Life Stealing', lifeStealTooltip, lifeStealAmount)}
      {renderPositiveRow(
        'Devouring Light',
        devouringLightTooltip,
        devouringLight
      )}
      {renderPositiveRow('Entrancing', entrancingTooltip, entrancedChance)}
      {renderNegativeRow(
        'Skill Reduction',
        `Reduces the enemy's skill by ${formatPercent(skillReduction)}.`,
        skillReduction
      )}
      {renderNegativeRow(
        'Resistance Reduction',
        `Reduces the enemy's resistance by ${formatPercent(resistanceReduction)}.`,
        resistanceReduction
      )}
    </Section>
  );
};

export default AffixMiscModifiersSection;
