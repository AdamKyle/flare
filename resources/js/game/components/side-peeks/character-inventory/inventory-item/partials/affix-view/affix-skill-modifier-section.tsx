import React from 'react';

import DefinitionRow from '../../../../../../reusable-components/viewable-sections/definition-row';
import InfoLabel from '../../../../../../reusable-components/viewable-sections/info-label';
import Section from '../../../../../../reusable-components/viewable-sections/section';
import StatRowPercent from '../../../../../../reusable-components/viewable-sections/stat-row-percent';
import { formatPercent } from '../../../../../../util/format-number';
import AffixSkillModifiersSectionProps from '../../types/partials/affix-view/affix-skill-modifier-section-props';

const AffixSkillModifiersSection = ({
  affix,
}: AffixSkillModifiersSectionProps) => {
  const skillBonus = Number(affix.skill_bonus ?? 0);
  const skillTrainingBonus = Number(affix.skill_training_bonus ?? 0);

  if (skillBonus <= 0 && skillTrainingBonus <= 0) {
    return null;
  }

  const skillBonusTooltip =
    `This is applied directly to the specified skill when you use it. It increases your chance of success for that skill by ${formatPercent(skillBonus)}. ` +
    `This stacks additively with other affixes that affect the same skill.`;

  const skillTrainingBonusTooltip =
    `This is applied directly to the XP you gain from training or using the skill. Whenever you earn XP for the skill, you gain ${formatPercent(skillTrainingBonus)} more. ` +
    `This stacks additively with other affixes that affect this skill.`;

  return (
    <Section title="Skill Modifiers">
      <DefinitionRow
        left={<InfoLabel label="Skill Name" />}
        right={<span className="font-semibold">{affix.skill_name}</span>}
      />

      <StatRowPercent
        label="Skill Bonus"
        value={skillBonus}
        tooltip={skillBonusTooltip}
      />

      <StatRowPercent
        label="Skill Training Bonus"
        value={skillTrainingBonus}
        tooltip={skillTrainingBonusTooltip}
      />
    </Section>
  );
};

export default AffixSkillModifiersSection;
