import React, { Fragment, useState } from 'react';
import { match } from 'ts-pattern';

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
    if (rawValue == null) {
      return false;
    }
    if (Number(rawValue) === 0) {
      return !!forceShowZeroKeys?.includes(fieldKey);
    }
    return true;
  };

  const isBaseModKey = (key: NumericAdjustmentKey): boolean => {
    return [
      'base_damage_mod_adjustment',
      'base_healing_mod_adjustment',
      'base_ac_mod_adjustment',
    ].includes(key);
  };

  const handleOpen = (id: string) => {
    setOpenId(id);
  };

  const handleClose = (id: string) => {
    if (openId === id) {
      setOpenId(null);
    }
  };

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
            on_open={() => handleOpen(id)}
            on_close={() => handleClose(id)}
            custom_message={useCustom}
          />
          <span className="min-w-0 break-words">{displayLabel}</span>
        </div>
      </Dt>
    );
  };

  const renderAdvancedChildFor = (parentKey: NumericAdjustmentKey) => {
    if (!showAdvancedChild) {
      return null;
    }

    const child = TOP_ADVANCED_CHILD[parentKey];

    if (!child) {
      return null;
    }

    const rawChildValue = adjustments[child.key] as number | null | undefined;
    if (rawChildValue == null) {
      return null;
    }
    if (Number(rawChildValue) === 0) {
      return null;
    }

    const numericChildValue = Number(rawChildValue);
    const forcePercent = isBaseModKey(child.key);
    const id = `child-${String(parentKey)}-${String(child.key)}`;

    const dir = numericChildValue > 0 ? 'increase' : 'decrease';
    const amount = `${(Math.abs(numericChildValue) * 100).toFixed(2)}%`;

    const type = match(child.key)
      .with('base_damage_mod_adjustment', () => 'Damage')
      .with('base_ac_mod_adjustment', () => 'Defence')
      .with('base_healing_mod_adjustment', () => 'Healing')
      .otherwise(() => parentKey);

    const customMessage = `This will ${dir} the over all ${child.label.toLowerCase()} by ${amount}. This can stack with other gear which contains this modifier to affect your over all ${type}, even if that gear doesnt increase your ${type}.`;

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

        if (!shouldRenderField(key, rawValue)) {
          return null;
        }

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
