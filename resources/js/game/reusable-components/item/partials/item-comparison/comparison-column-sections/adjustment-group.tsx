import React, { Fragment, useState } from 'react';

import { TOP_ADVANCED_CHILD } from '../../../constants/item-comparison-constants';
import StatInfoToolTip from '../../../stat-info-tool-tip';
import { NumericAdjustmentKey } from '../../../types/item-comparison-types';
import AdjustmentGroupProps from '../../../types/partials/item-comparison/adjustment-group-props';
import { hasAnyNonZeroAdjustment } from '../../../utils/item-comparison';
import AdjustmentChangeDisplay from '../adjustment-change-display';

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
  ) => {
    if (rawValue == null) return false;
    if (Number(rawValue) === 0) return !!forceShowZeroKeys?.includes(fieldKey);
    return true;
  };

  const isBaseModKey = (key: NumericAdjustmentKey) =>
    key === 'base_damage_mod_adjustment' ||
    key === 'base_healing_mod_adjustment' ||
    key === 'base_ac_mod_adjustment';

  const buildCustomMessage = (label: string, value: number) => {
    const direction =
      value > 0 ? 'increase' : value < 0 ? 'decrease' : 'not change';
    const pct = `${(Math.abs(value) * 100).toFixed(2)}%`;
    if (direction === 'not change') return `${label} will not change.`;
    return `This will ${direction} the items ${label} by ${pct}.`;
  };

  const renderDtWithInfo = (
    id: string,
    label: string,
    numericValue: number,
    forcePercent?: boolean,
    isCustomForBaseMod?: boolean
  ) => {
    const handleOpen = () => setOpenId(id);
    const handleClose = () => {
      if (openId === id) setOpenId(null);
    };

    const useCustom = !!isCustomForBaseMod;
    const customText = useCustom
      ? buildCustomMessage(label, numericValue)
      : undefined;

    return (
      <Dt>
        <StatInfoToolTip
          label={useCustom ? customText! : label}
          value={numericValue}
          renderAsPercent={forcePercent}
          align="left"
          size="sm"
          is_open={openId === id}
          on_open={handleOpen}
          on_close={handleClose}
          custom_message={useCustom}
        />
        <span className="min-w-0 break-words">{label}</span>
      </Dt>
    );
  };

  const renderAdvancedChildFor = (parentKey: NumericAdjustmentKey) => {
    if (!showAdvancedChild) return null;

    const child = TOP_ADVANCED_CHILD[parentKey];
    if (!child) return null;

    const rawChildValue = adjustments[child.key] as number | null | undefined;
    if (rawChildValue == null) return null;
    if (Number(rawChildValue) === 0) return null;

    const numericChildValue = Number(rawChildValue);
    const forcePercent = isBaseModKey(child.key as NumericAdjustmentKey);
    const id = `child-${String(parentKey)}-${String(child.key)}`;

    return (
      <Fragment>
        {renderDtWithInfo(
          id,
          child.label,
          numericChildValue,
          forcePercent,
          forcePercent
        )}
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
        if (!shouldRenderField(key as NumericAdjustmentKey, rawValue)) {
          return null;
        }

        const numericValue = Number(rawValue);
        const id = `field-${String(key)}`;
        const forcePercent = isBaseModKey(key as NumericAdjustmentKey);

        return (
          <Fragment key={String(key)}>
            {renderDtWithInfo(
              id,
              label,
              numericValue,
              forcePercent,
              forcePercent
            )}
            <Dd>
              <AdjustmentChangeDisplay
                value={numericValue}
                label={label}
                renderAsPercent={forcePercent}
              />
            </Dd>
            {renderAdvancedChildFor(key as NumericAdjustmentKey)}
          </Fragment>
        );
      })}
    </Dl>
  );
};

export default AdjustmentGroup;
