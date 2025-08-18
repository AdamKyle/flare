import React, { Fragment } from 'react';

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

function AffixAdjustmentsSection(props: {
  adjustments: ItemAdjustments;
  show: boolean;
}) {
  return null;
}

const ItemComparisonColumn = ({
  row,
  heading,
  index,
  showAdvanced,
  showAdvancedChildUnderTop,
}: ItemComparisonColumnProps) => {
  const adjustments = row?.comparison?.adjustments as
    | ItemAdjustments
    | undefined;

  if (!adjustments) return null;

  const computedTitle =
    heading ||
    row?.comparison?.to_equip_name ||
    `${row?.position ?? ''} ${row?.type ?? ''}`.trim() ||
    `Item ${index + 1}`;

  const description = row?.comparison?.to_equip_description;

  const hasCoreTotals = hasAnyNonZeroAdjustment(adjustments, TOP_FIELDS);
  const hasAttributes = hasAnyNonZeroAdjustment(adjustments, STAT_FIELDS);
  const showAffix =
    showAdvanced &&
    hasAnyNonZeroAdjustment(adjustments, AFFIX_ADJUSTMENT_FIELDS);
  const showDevouring =
    showAdvanced && hasAnyNonZeroAdjustment(adjustments, DEVOURING_FIELDS);

  const isTwoHanded = isTwoHandedType(row?.type);

  const forceCoreZeroKeys: NumericAdjustmentKey[] = showAdvancedChildUnderTop
    ? ([
        'total_defence_adjustment',
        'total_healing_adjustment',
      ] as NumericAdjustmentKey[])
    : [];

  return (
    <Fragment>
      <TitleAndDescription title={computedTitle} description={description} />

      <CurrentlyEquippedPanel
        position={row.position}
        equippedAffixName={row.comparison.equipped_affix_name}
        type={row.type}
        isTwoHanded={isTwoHanded}
      />

      <Legend />

      <CoreImpactSection
        adjustments={adjustments}
        hasCoreTotals={hasCoreTotals}
        showAdvancedChildUnderTop={showAdvancedChildUnderTop}
        forceCoreZeroKeys={forceCoreZeroKeys}
      />

      <ResurrectionChanceSection
        adjustments={adjustments}
        toEquipType={row.comparison.to_equip_type}
      />

      <ResistanceSection
        adjustments={adjustments}
        toEquipType={row.comparison.to_equip_type}
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
