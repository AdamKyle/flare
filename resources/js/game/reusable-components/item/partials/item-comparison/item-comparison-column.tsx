import React, { Fragment } from 'react';

import AdjustmentGroup from './adjustment-group';
import SkillSummary from './skill-summary';
import { ItemAdjustments } from '../../../../api-definitions/items/item-comparison-details';
import {
  AFFIX_ADJUSTMENT_FIELDS,
  DEVOURING_FIELDS,
  STAT_FIELDS,
  TOP_FIELDS,
} from '../../constants/item-comparison-constants';
import { NumericAdjustmentKey } from '../../types/item-comparison-types';
import ItemComparisonColumnProps from '../../types/partials/item-comparison/item-comparison-column';
import {
  getPositionLabel,
  hasAnyNonZeroAdjustment,
  isTwoHandedType,
} from '../../utils/item-comparison';

import Separator from 'ui/separator/separator';

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

  if (!adjustments) {
    return null;
  }

  const computedTitle =
    heading ||
    row?.comparison?.to_equip_name ||
    `${row?.position ?? ''} ${row?.type ?? ''}`.trim() ||
    `Item ${index + 1}`;

  const description = row?.comparison?.to_equip_description;

  const hasCoreTotals = hasAnyNonZeroAdjustment(adjustments, TOP_FIELDS);
  const hasAttributes = hasAnyNonZeroAdjustment(adjustments, STAT_FIELDS);

  // Advanced sections
  const shouldShowAffixSection =
    showAdvanced &&
    hasAnyNonZeroAdjustment(adjustments, AFFIX_ADJUSTMENT_FIELDS);

  const shouldShowDevouringSection =
    showAdvanced && hasAnyNonZeroAdjustment(adjustments, DEVOURING_FIELDS);

  const isTwoHanded = isTwoHandedType(row?.type);

  const forceCoreZeroKeys: NumericAdjustmentKey[] = showAdvancedChildUnderTop
    ? ([
        'total_defence_adjustment',
        'total_healing_adjustment',
      ] as NumericAdjustmentKey[])
    : [];

  const renderTitle = () => {
    return (
      <h3 className="mb-1 text-lg font-semibold text-gray-900 dark:text-gray-200">
        {computedTitle}
      </h3>
    );
  };

  const renderDescription = () => {
    if (!description) {
      return null;
    }

    return (
      <p className="mb-2 text-sm text-gray-700 dark:text-gray-300">
        {description}
      </p>
    );
  };

  const renderType = () => {
    if (!row.type) {
      return null;
    }

    return (
      <>
        <span className="mx-2 text-gray-500 dark:text-gray-400">•</span>
        <span className="font-medium">Type:</span>{' '}
        <span className="capitalize">{row.type}</span>
      </>
    );
  };

  const renderTwoHanded = () => {
    if (!isTwoHanded) {
      return null;
    }

    return (
      <>
        <span className="mx-2 text-gray-500 dark:text-gray-400">•</span>
        <span className="font-medium">Two-Handed</span>
      </>
    );
  };

  const renderReplacementLine = () => {
    return (
      <div className="mb-3 text-sm leading-snug text-gray-900 dark:text-gray-100">
        <span className="font-medium">
          Replacing {getPositionLabel(row.position)}:
        </span>{' '}
        <span className="italic">{row.comparison.equipped_affix_name}</span>
        {renderType()}
        {renderTwoHanded()}
      </div>
    );
  };

  const renderLegend = () => {
    return (
      <div className="mb-3 flex items-center justify-between text-sm leading-snug text-gray-700 dark:text-gray-300">
        <span>If equipped (net change)</span>
        <span className="flex items-center gap-3">
          <span className="flex items-center gap-1">
            <i
              className="fas fa-chevron-up text-emerald-600"
              aria-hidden="true"
            />
            <span>gain</span>
          </span>
          <span className="flex items-center gap-1">
            <i
              className="fas fa-chevron-down text-rose-600"
              aria-hidden="true"
            />
            <span>loss</span>
          </span>
        </span>
      </div>
    );
  };

  const renderCoreImpact = () => {
    const shouldShowCore = hasCoreTotals || forceCoreZeroKeys.length > 0;

    if (!shouldShowCore) {
      return null;
    }

    return (
      <Fragment>
        <h4 className="mt-2 mb-1 text-xs font-semibold uppercase tracking-wide text-mango-tango-500 dark:text-mango-tango-300">
          Core Impact
        </h4>
        <Separator />
        <AdjustmentGroup
          adjustments={adjustments}
          fields={TOP_FIELDS}
          showAdvancedChild={showAdvancedChildUnderTop}
          forceShowZeroKeys={forceCoreZeroKeys}
        />
      </Fragment>
    );
  };

  const renderAttributes = () => {
    if (!hasAttributes) {
      return null;
    }

    return (
      <Fragment>
        <h4 className="mt-3 mb-1 text-xs font-semibold uppercase tracking-wide text-mango-tango-500 dark:text-mango-tango-300">
          Attributes
        </h4>
        <Separator />
        <AdjustmentGroup adjustments={adjustments} fields={STAT_FIELDS} />
      </Fragment>
    );
  };

  const renderAffixAdjustments = () => {
    if (!shouldShowAffixSection) {
      return null;
    }

    return (
      <Fragment>
        <h4 className="mt-3 mb-1 text-xs font-semibold uppercase tracking-wide text-mango-tango-500 dark:text-mango-tango-300">
          Affix Adjustments
        </h4>
        <Separator />
        <AdjustmentGroup
          adjustments={adjustments}
          fields={AFFIX_ADJUSTMENT_FIELDS}
        />
      </Fragment>
    );
  };

  const renderSkills = () => {
    return (
      <Fragment>
        <h4 className="mt-3 mb-1 text-xs font-semibold uppercase tracking-wide text-mango-tango-500 dark:text-mango-tango-300">
          Skills
        </h4>
        <Separator />
        <SkillSummary adjustments={adjustments} />
      </Fragment>
    );
  };

  const renderDevouring = () => {
    if (!shouldShowDevouringSection) {
      return null;
    }

    return (
      <Fragment>
        <h4 className="mt-3 mb-1 text-xs font-semibold uppercase tracking-wide text-mango-tango-500 dark:text-mango-tango-300">
          Devouring Adjustments
        </h4>
        <Separator />
        <AdjustmentGroup adjustments={adjustments} fields={DEVOURING_FIELDS} />
        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
          <em>
            Devouring Darkness voids the enemies&apos; chance to void your
            enchantments, while Devouring Light voids the enemies&apos; special
            enchantments. This can turn the battle in your favour.
          </em>
        </p>
      </Fragment>
    );
  };

  return (
    <Fragment>
      {renderTitle()}
      {renderDescription()}
      {renderReplacementLine()}
      {renderLegend()}
      {renderCoreImpact()}
      {renderAttributes()}
      {renderAffixAdjustments()}
      {renderSkills()}
      {renderDevouring()}
    </Fragment>
  );
};

export default ItemComparisonColumn;
