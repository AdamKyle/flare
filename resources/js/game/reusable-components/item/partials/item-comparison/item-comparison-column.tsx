import React, { Fragment } from 'react';

import AdjustmentChangeDisplay from './adjustment-change-display';
import AdjustmentGroup from './adjustment-group';
import SkillSummary from './skill-summary';
import { ItemAdjustments } from '../../../../api-definitions/items/item-comparison-details';
import { InventoryItemTypes } from '../../../../components/character-sheet/partials/character-inventory/enums/inventory-item-types';
import {
  AFFIX_ADJUSTMENT_FIELDS,
  DEVOURING_FIELDS,
  STAT_FIELDS,
  TOP_FIELDS,
} from '../../constants/item-comparison-constants';
import StatInfoToolTip from '../../stat-info-tool-tip';
import type {
  FieldDef,
  NumericAdjustmentKey,
} from '../../types/item-comparison-types';
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
      <h3 className="mb-1 text-lg font-semibold text-gray-900 dark:text-gray-200 break-words">
        {computedTitle}
      </h3>
    );
  };

  const renderDescription = () => {
    if (!description) return null;

    return (
      <p className="mb-2 text-sm text-gray-700 dark:text-gray-300 break-words">
        {description}
      </p>
    );
  };

  const renderType = () => {
    if (!row.type) return null;

    return (
      <>
        <span className="mx-2 text-gray-500 dark:text-gray-400">•</span>
        <span className="font-medium">Type:</span>{' '}
        <span className="capitalize">{row.type}</span>
      </>
    );
  };

  const renderTwoHanded = () => {
    if (!isTwoHanded) return null;

    return (
      <>
        <span className="mx-2 text-gray-500 dark:text-gray-400">•</span>
        <span className="font-medium">Two-Handed</span>
      </>
    );
  };

  const renderReplacementLine = () => {
    return (
      <div className="mb-3 text-sm leading-snug text-gray-900 dark:text-gray-100 break-words">
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
    if (!shouldShowCore) return null;

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

  // --- NEW SECTION: Resurrection Chance (spell-healing only) ---
  const renderResurrectionChance = () => {
    if (row?.comparison?.to_equip_type !== InventoryItemTypes.SPELL_HEALING) {
      return null;
    }

    const raw = adjustments.resurrection_chance_adjustment;
    if (raw == null) {
      return null;
    }

    const value = Number(raw);
    if (value === 0) {
      return null;
    }

    const direction = value > 0 ? 'increases' : 'decreases';
    const amountPct = `${(Math.abs(value) * 100).toFixed(2)}%`;
    const customMessage =
      `This ${direction} your chance by ${amountPct} to be resurrected on death, ` +
      `having two healing spells stacks the chance.`;

    return (
      <Fragment>
        <h4 className="mt-3 mb-1 text-xs font-semibold uppercase tracking-wide text-mango-tango-500 dark:text-mango-tango-300">
          Resurrection Chance
        </h4>
        <Separator />
        <dl className="grid grid-cols-[1fr_auto] items-center gap-x-4 gap-y-1">
          <dt className="font-medium text-gray-900 dark:text-gray-100">
            <div className="flex items-center">
              <StatInfoToolTip
                label={customMessage}
                value={value}
                renderAsPercent
                align="left"
                size="sm"
                custom_message
              />
              <span className="min-w-0 break-words">Chance</span>
            </div>
          </dt>
          <dd>
            <AdjustmentChangeDisplay
              value={value}
              label="Chance"
              renderAsPercent
            />
          </dd>
        </dl>
      </Fragment>
    );
  };
  // ------------------------------------------------------------

  // --- Existing: Resistance Adjustments (rings only) ---
  const RESISTANCE_FIELDS: FieldDef[] = [
    { key: 'spell_evasion_adjustment', label: 'Spell Evasion' },
    { key: 'healing_reduction_adjustment', label: 'Healing Reduction' },
    {
      key: 'affix_damage_reduction_adjustment',
      label: 'Affix Damage Reduction',
    },
  ];

  const renderResistanceAdjustments = () => {
    if (row?.comparison?.to_equip_type !== InventoryItemTypes.RING) {
      return null;
    }

    const hasAnyResistance = hasAnyNonZeroAdjustment(
      adjustments,
      RESISTANCE_FIELDS
    );

    if (!hasAnyResistance) {
      return null;
    }

    const renderRow = (label: string, raw: number | null | undefined) => {
      const value = Number(raw ?? 0);
      if (value === 0) return null;

      return (
        <Fragment key={label}>
          <dt className="font-medium text-gray-900 dark:text-gray-100">
            <div className="flex items-center">
              <StatInfoToolTip
                label={label.toLowerCase()}
                value={value}
                renderAsPercent
                align="left"
                size="sm"
              />
              <span className="min-w-0 break-words">{label}</span>
            </div>
          </dt>
          <dd>
            <AdjustmentChangeDisplay
              value={value}
              label={label}
              renderAsPercent
            />
          </dd>
        </Fragment>
      );
    };

    return (
      <Fragment>
        <h4 className="mt-3 mb-1 text-xs font-semibold uppercase tracking-wide text-mango-tango-500 dark:text-mango-tango-300">
          Resistance Adjustments
        </h4>
        <Separator />
        <dl className="grid grid-cols-[1fr_auto] items-center gap-x-4 gap-y-1">
          {renderRow('Spell Evasion', adjustments.spell_evasion_adjustment)}
          {renderRow(
            'Healing Reduction',
            adjustments.healing_reduction_adjustment
          )}
          {renderRow(
            'Affix Damage Reduction',
            adjustments.affix_damage_reduction_adjustment
          )}
        </dl>
      </Fragment>
    );
  };
  // -------------------------------------------------------

  const renderAttributes = () => {
    if (!hasAttributes) return null;

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
    if (!shouldShowAffixSection) return null;

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
    if (
      !Array.isArray(adjustments.skill_summary) ||
      adjustments.skill_summary.length <= 0
    ) {
      return null;
    }

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
    if (!shouldShowDevouringSection) return null;

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
      {renderResurrectionChance()}
      {renderResistanceAdjustments()}
      {renderAttributes()}
      {renderAffixAdjustments()}
      {renderSkills()}
      {renderDevouring()}
    </Fragment>
  );
};

export default ItemComparisonColumn;
