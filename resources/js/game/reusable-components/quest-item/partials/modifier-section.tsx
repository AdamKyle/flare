import React from 'react';

import { formatPercent } from '../../../util/format-number';
import DefinitionRow from '../../viewable-sections/definition-row';
import InfoAlerts from '../../viewable-sections/info-alert';
import InfoLabel from '../../viewable-sections/info-label';
import Section from '../../viewable-sections/section';
import ModifiersSectionProps from '../types/partials/modifer-section-props';

const ModifiersSection = ({ item, showSeparator }: ModifiersSectionProps) => {
  const hasMove = item.move_time_out_mod_bonus !== 0;
  const hasFight = item.fight_time_out_mod_bonus !== 0;
  const hasSkillBonus =
    item.skill_name !== null &&
    item.skill_bonus !== null &&
    item.skill_bonus > 0;
  const hasSkillTrainingBonus =
    item.skill_name !== null &&
    item.skill_training_bonus !== null &&
    item.skill_training_bonus > 0;

  if (!hasMove && !hasFight && !hasSkillBonus && !hasSkillTrainingBonus) {
    return null;
  }

  const messages: string[] = [];
  if (hasFight) {
    messages.push(
      'This quest item stacks with other items and skills to decrease the time-out between manual fights.'
    );
  }
  if (hasMove) {
    messages.push(
      'This quest item stacks with other items and skills to decrease the time between movement on all maps, including teleport and directional movement.'
    );
  }

  const renderModifierRow = (label: string, percentValue: number) => {
    if (percentValue === 0) {
      return null;
    }

    const proportion = percentValue / 100;

    return (
      <DefinitionRow
        left={<InfoLabel label={label} />}
        right={
          <span className="font-semibold text-emerald-700 dark:text-emerald-300">
            {formatPercent(proportion)}
          </span>
        }
      />
    );
  };

  const renderSkillModifierRow = (
    skillName: string | null,
    value: number | null,
    suffix = ''
  ) => {
    if (skillName === null || value === null || value <= 0) {
      return null;
    }

    return (
      <DefinitionRow
        left={<InfoLabel label={`${skillName}${suffix}`} />}
        right={
          <span className="inline-flex items-center gap-2 font-semibold whitespace-nowrap text-emerald-700 tabular-nums dark:text-emerald-300">
            <i
              className="fas fa-chevron-up text-emerald-600 dark:text-emerald-400"
              aria-hidden="true"
            />
            {formatPercent(value, 2, 0)}
          </span>
        }
      />
    );
  };

  return (
    <Section
      title="Modifiers"
      showSeparator={showSeparator}
      lead={<InfoAlerts messages={messages} />}
    >
      {renderModifierRow(
        'Fight Timeout Modifier',
        item.fight_time_out_mod_bonus ?? 0
      )}
      {renderModifierRow(
        'Move Timeout Modifier',
        item.move_time_out_mod_bonus ?? 0
      )}
      {renderSkillModifierRow(item.skill_name, item.skill_bonus)}
      {renderSkillModifierRow(
        item.skill_name,
        item.skill_training_bonus,
        ' Training'
      )}
    </Section>
  );
};

export default ModifiersSection;
