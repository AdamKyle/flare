import { isNil } from 'lodash';
import React from 'react';

import DefinitionRow from '../../../../../../reusable-components/viewable-sections/definition-row';
import InfoLabel from '../../../../../../reusable-components/viewable-sections/info-label';
import Section from '../../../../../../reusable-components/viewable-sections/section';
import { formatSignedPercent } from '../../../../../../util/format-number';
import AffixCoreAttributesSectionProps from '../../types/partials/affix-view/affix-core-attributes-section';

const AffixCoreAttributesSection = ({
  affix,
}: AffixCoreAttributesSectionProps) => {
  const buildAttributeTooltip = (attributeLabel: string) => {
    return `This ${attributeLabel.toLowerCase()} is applied directly to the item's ${attributeLabel.toLowerCase()}, which in turn is applied to the character. This value stacks additively with other affixes; the combined total is applied to the item's ${attributeLabel.toLowerCase()}.`;
  };

  const baseDamageMod = Number(affix.base_damage_mod ?? 0);
  const baseAcMod = Number(affix.base_ac_mod ?? 0);
  const baseHealingMod = Number(affix.base_healing_mod ?? 0);

  const allCoreAttributesZero =
    baseDamageMod <= 0 && baseAcMod <= 0 && baseHealingMod <= 0;

  if (allCoreAttributesZero) {
    return null;
  }

  const renderCoreRow = (label: string, value: number | null) => {
    if (isNil(value)) {
      return null;
    }

    const numericValue = Number(value);

    if (numericValue <= 0) {
      return null;
    }

    return (
      <DefinitionRow
        left={
          <InfoLabel
            label={label}
            tooltip={buildAttributeTooltip(label)}
            tooltipValue={numericValue}
            tooltipAlign="right"
            tooltipRenderAsPercent
            tooltipSize="sm"
          />
        }
        right={
          <span className="inline-flex items-center gap-2 whitespace-nowrap">
            <i
              className="fas fa-chevron-up text-emerald-600"
              aria-hidden="true"
            />
            <span className="font-semibold text-emerald-700 tabular-nums">
              {formatSignedPercent(numericValue)}
            </span>
          </span>
        }
      />
    );
  };

  return (
    <Section title="Core Attributes">
      {renderCoreRow('Base Damage Mod', affix.base_damage_mod)}
      {renderCoreRow('Base AC Mod', affix.base_ac_mod)}
      {renderCoreRow('Base Healing Mod', affix.base_healing_mod)}
    </Section>
  );
};

export default AffixCoreAttributesSection;
