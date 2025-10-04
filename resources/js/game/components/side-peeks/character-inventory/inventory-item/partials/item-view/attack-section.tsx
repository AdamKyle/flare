import React from 'react';

import DefinitionRow from '../../../../../../reusable-components/viewable-sections/definition-row';
import InfoLabel from '../../../../../../reusable-components/viewable-sections/info-label';
import Section from '../../../../../../reusable-components/viewable-sections/section';
import {
  formatIntWithPlus,
  formatSignedPercent,
} from '../../../../../../util/format-number';
import AttackSectionProps from '../../types/partials/item-view/attack-section-props';
import {
  attackToolTipDescription,
  baseDamageModifierToolTipDescription,
} from '../../utils/tool-tip-description-builder';

const AttackSection = ({ attack, baseDamageMod }: AttackSectionProps) => {
  const baseMod = Number(baseDamageMod ?? 0);
  const hasChild = baseMod > 0;

  if (attack === 0 && !hasChild) {
    return null;
  }

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
              tooltip={baseDamageModifierToolTipDescription(baseMod)}
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
            tooltip={attackToolTipDescription(attack)}
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
