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
    if (rawValue == null) {
      return false;
    }

    if (Number(rawValue) === 0) {
      return !!forceShowZeroKeys?.includes(fieldKey);
    }

    return true;
  };

  const isBaseModKey = (key: NumericAdjustmentKey): boolean => {
    if (key === 'base_damage_mod_adjustment') {
      return true;
    }

    if (key === 'base_healing_mod_adjustment') {
      return true;
    }

    if (key === 'base_ac_mod_adjustment') {
      return true;
    }

    return false;
  };

  const renderDtWithInfo = (
    id: string,
    label: string,
    numericValue: number,
    forcePercent?: boolean
  ) => {
    const handleOpen = () => {
      setOpenId(id);
    };

    const handleClose = () => {
      if (openId === id) {
        setOpenId(null);
      }
    };

    return (
      <Dt>
        <StatInfoToolTip
          label={label}
          value={numericValue}
          renderAsPercent={forcePercent}
          align="left"
          size="sm"
          is_open={openId === id}
          on_open={handleOpen}
          on_close={handleClose}
        />
        <span className="min-w-0 break-words">{label}</span>
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

    return (
      <Fragment>
        {renderDtWithInfo(id, child.label, numericChildValue, forcePercent)}
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
