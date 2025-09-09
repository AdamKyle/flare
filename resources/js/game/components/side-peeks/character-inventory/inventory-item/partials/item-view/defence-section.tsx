import React from 'react';

import DefinitionRow from './definition-row';
import InfoLabel from './info-label';
import Section from './section';
import {
  formatIntWithPlus,
  formatSignedPercent,
} from '../../../../../../util/format-number';
import DefenceSectionProps from '../../types/partials/item-view/defence-section-props';

const DefenceSection = ({ ac, baseAcMod }: DefenceSectionProps) => {
  const baseMod = Number(baseAcMod ?? 0);
  const hasChild = baseMod > 0;

  if (ac === 0 && !hasChild) {
    return null;
  }

  const dir = baseMod > 0 ? 'increase' : 'decrease';
  const amount = formatSignedPercent(baseMod).replace(/^[+-]/, '');
  const baseModTooltip =
    `This will ${dir} the armour base AC by ${amount}. ` +
    `This can stack with other gear that contains this modifier to affect your overall defence, ` +
    `even if that gear doesn’t increase your defence.`;

  const renderUpIcon = (value: number) => {
    if (value <= 0) {
      return null;
    }

    return (
      <i className="fas fa-chevron-up text-emerald-600" aria-hidden="true" />
    );
  };

  const renderBaseModRow = () => {
    if (baseMod <= 0) {
      return null;
    }

    return (
      <DefinitionRow
        left={
          <div className="ml-4 inline-flex items-center gap-2">
            <InfoLabel
              label="Base AC Mod"
              tooltip={baseModTooltip}
              tooltipValue={baseMod}
              tooltipAlign="right"
              tooltipRenderAsPercent
              tooltipSize="sm"
            />
          </div>
        }
        right={
          <span className="ml-4 inline-flex items-center gap-2 whitespace-nowrap">
            {renderUpIcon(baseMod)}
            <span className="font-semibold text-emerald-700 tabular-nums">
              {formatSignedPercent(baseMod)}
            </span>
          </span>
        }
      />
    );
  };

  return (
    <Section title="Defence">
      <DefinitionRow
        left={
          <InfoLabel
            label="AC"
            tooltip="AC"
            tooltipValue={ac}
            tooltipAlign="right"
          />
        }
        right={
          <span className="inline-flex items-center gap-2">
            {renderUpIcon(ac)}
            <span className="font-semibold tabular-nums">
              {formatIntWithPlus(ac)}
            </span>
          </span>
        }
      />

      {renderBaseModRow()}
    </Section>
  );
};

export default DefenceSection;
