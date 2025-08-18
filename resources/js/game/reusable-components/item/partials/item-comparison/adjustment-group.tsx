import React, { Fragment, useState } from 'react';

import AdjustmentChangeDisplay from './adjustment-change-display';
import { TOP_ADVANCED_CHILD } from '../../constants/item-comparison-constants';
import StatInfoToolTip from '../../stat-info-tool-tip';
import { NumericAdjustmentKey } from '../../types/item-comparison-types';
import AdjustmentGroupProps from '../../types/partials/item-comparison/adjustment-group-props';
import { hasAnyNonZeroAdjustment } from '../../utils/item-comparison';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const AdjustmentGroup = ({
  adjustments,
  fields,
  showAdvancedChild,
  forceShowZeroKeys,
}: AdjustmentGroupProps) => {
  const [openId, setOpenId] = useState<string | null>(null);

  const hasNonZero = hasAnyNonZeroAdjustment(adjustments, fields);
  const hasForced =
    Array.isArray(forceShowZeroKeys) && forceShowZeroKeys.length > 0;

  if (!hasNonZero && !hasForced) {
    return null;
  }

  const shouldRenderField = (
    fieldKey: NumericAdjustmentKey,
    rawValue: number | null | undefined
  ): boolean => {
    if (rawValue == null) return false;

    if (Number(rawValue) === 0) {
      return !!forceShowZeroKeys?.includes(fieldKey);
    }

    return true;
  };

  const isBaseModKey = (key: NumericAdjustmentKey): boolean => {
    if (key === 'base_damage_mod_adjustment') return true;
    if (key === 'base_healing_mod_adjustment') return true;
    if (key === 'base_ac_mod_adjustment') return true;
    return false;
  };

  /**
   * Common DT renderer with optional:
   *  - customMessage: overrides the tooltip sentence (uses StatInfoToolTip.custom_message)
   *  - indent: indents the row (used for advanced children)
   */
  const renderDtWithInfo = (
    id: string,
    displayLabel: string,
    numericValue: number,
    forcePercent?: boolean,
    opts?: {
      customMessage?: string;
      indent?: boolean;
    }
  ) => {
    const handleOpen = () => setOpenId(id);
    const handleClose = () => {
      if (openId === id) setOpenId(null);
    };

    const indentClass = opts?.indent ? ' ml-4' : '';
    const useCustom = typeof opts?.customMessage === 'string';

    return (
      <Dt>
        <div className={`flex items-center${indentClass}`}>
          <StatInfoToolTip
            label={useCustom ? (opts!.customMessage as string) : displayLabel}
            value={numericValue}
            renderAsPercent={!!forcePercent}
            align="left"
            size="sm"
            is_open={openId === id}
            on_open={handleOpen}
            on_close={handleClose}
            custom_message={useCustom}
          />
          <span className="min-w-0 break-words">{displayLabel}</span>
        </div>
      </Dt>
    );
  };

  /**
   * Advanced children under Core Impact
   * (Base Damage Mod, Base Healing Mod, Base AC Mod)
   * - Indented
   * - Tooltip says: “This will increase/decrease the items {label} by X%.”
   */
  const renderAdvancedChildFor = (parentKey: NumericAdjustmentKey) => {
    if (!showAdvancedChild) return null;

    const child = TOP_ADVANCED_CHILD[parentKey];
    if (!child) return null;

    const rawChildValue = adjustments[child.key] as number | null | undefined;
    if (rawChildValue == null) return null;
    if (Number(rawChildValue) === 0) return null;

    const numericChildValue = Number(rawChildValue);
    const forcePercent = isBaseModKey(child.key);
    const id = `child-${String(parentKey)}-${String(child.key)}`;

    // Build the exact sentence you asked for:
    const dir = numericChildValue > 0 ? 'increase' : 'decrease';
    const amount = `${(Math.abs(numericChildValue) * 100).toFixed(2)}%`;
    const customMessage = `This will ${dir} the items ${child.label.toLowerCase()} by ${amount}.`;

    return (
      <Fragment>
        {renderDtWithInfo(id, child.label, numericChildValue, forcePercent, {
          customMessage,
          indent: true,
        })}
        <Dd>
          <div className="ml-4">
            <AdjustmentChangeDisplay
              value={numericChildValue}
              label={child.label}
              renderAsPercent={forcePercent}
            />
          </div>
        </Dd>
      </Fragment>
    );
  };

  return (
    <Dl>
      {fields.map(({ key, label }) => {
        const rawValue = adjustments[key] as number | null | undefined;
        if (!shouldRenderField(key, rawValue)) return null;

        const numericValue = Number(rawValue);
        const id = `field-${String(key)}`;

        return (
          <Fragment key={String(key)}>
            {renderDtWithInfo(id, label, numericValue)}
            <Dd>
              <AdjustmentChangeDisplay value={numericValue} label={label} />
            </Dd>
            {renderAdvancedChildFor(key)}
          </Fragment>
        );
      })}
    </Dl>
  );
};

export default AdjustmentGroup;
