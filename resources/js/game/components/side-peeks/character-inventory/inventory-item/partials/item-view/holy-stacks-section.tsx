import React from 'react';

import DefinitionRow from '../../../../../../reusable-components/viewable-sections/definition-row';
import InfoLabel from '../../../../../../reusable-components/viewable-sections/info-label';
import Section from '../../../../../../reusable-components/viewable-sections/section';
import { formatPercent } from '../../../../../../util/format-number';
import HolyStacksSectionProps from '../../types/partials/item-view/holy-stacks-section-props';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LinkButton from 'ui/buttons/link-button';

const HolyStacksSection = ({
  total = 0,
  applied = 0,
  attributeBonus = 0,
  devouringDarknessBonus = 0,
  onClickApplied,
}: HolyStacksSectionProps) => {
  const nothingToShow =
    Number(total) <= 0 &&
    Number(applied) <= 0 &&
    Number(attributeBonus) <= 0 &&
    Number(devouringDarknessBonus) <= 0;

  if (nothingToShow) {
    return null;
  }

  const renderUpIcon = (value: number | null) => {
    if (!value || value <= 0) {
      return null;
    }

    return (
      <i className="fas fa-chevron-up text-emerald-600" aria-hidden="true" />
    );
  };

  const renderTotalRow = () => {
    if (Number(total) <= 0) {
      return null;
    }

    return (
      <DefinitionRow
        left={
          <InfoLabel
            label="Total Holy Stacks"
            tooltip="This represents how many holy oils you can apply to the item."
            tooltipValue={0}
            tooltipAlign="right"
            tooltipSize="sm"
            tooltipRenderAsPercent={false}
          />
        }
        right={<span className="font-semibold">{total}</span>}
      />
    );
  };

  const renderAppliedRow = () => {
    if (Number(applied) <= 0) {
      return null;
    }

    return (
      <DefinitionRow
        left={
          <InfoLabel
            label="Total Applied Holy Stacks"
            tooltip="This is a breakdown of all the holy oils you have applied. Click the number for a deeper understanding."
            tooltipValue={Number(applied ?? 0)}
            tooltipAlign="right"
            tooltipSize="sm"
            tooltipRenderAsPercent={false}
          />
        }
        right={
          <LinkButton
            label={String(applied)}
            variant={ButtonVariant.SUCCESS}
            on_click={onClickApplied ?? (() => {})}
          />
        }
      />
    );
  };

  const renderAttributeBonusRow = () => {
    if (Number(attributeBonus) <= 0) {
      return null;
    }

    return (
      <DefinitionRow
        left={
          <InfoLabel
            label="Holy Stack Attribute Bonus"
            tooltip={`This value (${formatPercent(Number(attributeBonus))}) is applied to all your stats and stacks with any and all other stat bonuses on any and all gear.`}
            tooltipAlign="right"
            tooltipSize="sm"
            tooltipRenderAsPercent={false}
          />
        }
        right={
          <span className="inline-flex items-center gap-2">
            {renderUpIcon(attributeBonus)}
            <span className="font-semibold text-emerald-700 tabular-nums">
              {formatPercent(Number(attributeBonus))}
            </span>
          </span>
        }
      />
    );
  };

  const renderDevouringDarknessRow = () => {
    if (Number(devouringDarknessBonus) <= 0) {
      return null;
    }

    return (
      <DefinitionRow
        left={
          <InfoLabel
            label="Holy Stacks Devouring Darkness Bonus"
            tooltip="This value affects your ability to overcome enemies’ attempts to void your enchantments, and it stacks with other items that affect Devouring Darkness."
            tooltipAlign="right"
            tooltipSize="sm"
            tooltipRenderAsPercent={false}
          />
        }
        right={
          <span className="inline-flex items-center gap-2">
            {renderUpIcon(devouringDarknessBonus)}
            <span className="font-semibold text-emerald-700 tabular-nums">
              {formatPercent(Number(devouringDarknessBonus))}
            </span>
          </span>
        }
      />
    );
  };

  return (
    <Section title="Holy Stacks" showSeparator={false}>
      {renderTotalRow()}
      {renderAppliedRow()}
      {renderAttributeBonusRow()}
      {renderDevouringDarknessRow()}
    </Section>
  );
};

export default HolyStacksSection;
