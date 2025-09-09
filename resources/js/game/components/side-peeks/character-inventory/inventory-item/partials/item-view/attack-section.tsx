import React from 'react';

import DefinitionRow from './definition-row';
import InfoLabel from './info-label';
import Section from './section';
import {
  formatIntWithPlus,
  formatSignedPercent,
} from '../../../../../../util/format-number';
import AttackSectionProps from '../../types/partials/item-view/attack-section-props';

const AttackSection = ({ attack, baseDamageMod }: AttackSectionProps) => {
  const baseMod = Number(baseDamageMod ?? 0);
  const hasChild = baseMod > 0;

  if (attack === 0 && !hasChild) {
    return null;
  }

  const dir = baseMod > 0 ? 'increase' : 'decrease';
  const amount = formatSignedPercent(baseMod).replace(/^[+-]/, '');
  const baseModTooltip =
    `This will ${dir} the weapon or spell base damage by ${amount}. ` +
    `This can stack with other gear that contains this modifier to affect your overall damage, ` +
    `even if that gear doesn’t increase your damage.`;

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
              label="Base Damage Mod"
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
    <Section title="Attack">
      <DefinitionRow
        left={
          <InfoLabel
            label="Attack"
            tooltip="Attack"
            tooltipValue={attack}
            tooltipAlign="right"
          />
        }
        right={
          <span className="inline-flex items-center gap-2">
            {renderUpIcon(attack)}
            <span className="font-semibold tabular-nums">
              {formatIntWithPlus(attack)}
            </span>
          </span>
        }
      />

      {renderBaseModRow()}
    </Section>
  );
};

export default AttackSection;
