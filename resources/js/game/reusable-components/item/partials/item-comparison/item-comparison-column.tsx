import React, { Fragment } from 'react';

import AffixAdjustmentsSection from './comparison-column-sections/affix-adjustment-section';
import AttributesSection from './comparison-column-sections/attributes-section';
import CoreImpactSection from './comparison-column-sections/core-impact-section';
import CurrentlyEquippedPanel from './comparison-column-sections/currently-equipped-panel';
import DevouringSection from './comparison-column-sections/devouring-section';
import Legend from './comparison-column-sections/legend';
import ResistanceSection from './comparison-column-sections/resistance-section';
import ResurrectionChanceSection from './comparison-column-sections/resurrection-chance-section';
import SkillsSection from './comparison-column-sections/skill-section';
import TitleAndDescription from './comparison-column-sections/title-and-description';
import { ItemAdjustments } from '../../../../api-definitions/items/item-comparison-details';
import {
  AFFIX_ADJUSTMENT_FIELDS,
  DEVOURING_FIELDS,
  STAT_FIELDS,
  TOP_FIELDS,
} from '../../constants/item-comparison-constants';
import type { NumericAdjustmentKey } from '../../types/item-comparison-types';
import ItemComparisonColumnProps from '../../types/partials/item-comparison/item-comparison-column';
import {
  hasAnyNonZeroAdjustment,
  isTwoHandedType,
} from '../../utils/item-comparison';

const ItemComparisonColumn = ({
  row,
  showAdvanced,
  showAdvancedChildUnderTop,
  showHeaderSection = true,
}: ItemComparisonColumnProps) => {
  const adjustments = row?.comparison?.adjustments as
    | ItemAdjustments
    | undefined;

  if (!adjustments) {
    return null;
  }

  const getForceCoreZeroKeys = (): NumericAdjustmentKey[] => {
    if (!showAdvancedChildUnderTop) {
      return [];
    }
    return [
      'total_defence_adjustment',
      'total_healing_adjustment',
    ] as NumericAdjustmentKey[];
  };

  const hasCoreTotals = hasAnyNonZeroAdjustment(adjustments, TOP_FIELDS);
  const hasAttributes = hasAnyNonZeroAdjustment(adjustments, STAT_FIELDS);
  const showAffix =
    showAdvanced &&
    hasAnyNonZeroAdjustment(adjustments, AFFIX_ADJUSTMENT_FIELDS);
  const showDevouring =
    showAdvanced && hasAnyNonZeroAdjustment(adjustments, DEVOURING_FIELDS);

  const isTwoHanded = isTwoHandedType(row.equipped_item.type);
  const forceCoreZeroKeys = getForceCoreZeroKeys();

  const renderHeader = () => {
    if (!showHeaderSection) {
      return null;
    }

    return (
      <>
        <TitleAndDescription item={row.equipped_item} />

        <CurrentlyEquippedPanel
          position={row.position}
          equippedItem={row.equipped_item}
          isTwoHanded={isTwoHanded}
        />
      </>
    );
  };

  return (
    <Fragment>
      {renderHeader()}

      <Legend />

      <CoreImpactSection
        adjustments={adjustments}
        hasCoreTotals={hasCoreTotals}
        showAdvancedChildUnderTop={showAdvancedChildUnderTop}
        forceCoreZeroKeys={forceCoreZeroKeys}
      />

      <ResurrectionChanceSection
        adjustments={adjustments}
        toEquipType={row.equipped_item.type}
      />

      <ResistanceSection
        adjustments={adjustments}
        toEquipType={row.equipped_item.type}
      />

      <AttributesSection
        adjustments={adjustments}
        hasAttributes={hasAttributes}
      />

      <AffixAdjustmentsSection adjustments={adjustments} show={showAffix} />

      <SkillsSection adjustments={adjustments} />

      <DevouringSection adjustments={adjustments} show={showDevouring} />
    </Fragment>
  );
};

export default ItemComparisonColumn;
