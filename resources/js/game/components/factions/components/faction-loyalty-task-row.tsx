import React, { ReactNode, useId } from 'react';

import FactionLoyaltyTaskRowProps from './types/faction-loyalty-task-row-props';
import { resolveFactionLoyaltyTaskLabel } from '../utils/resolve-faction-loyalty-task-label';

import { ProgressBarSize } from 'ui/progress/enums/progress-bar-size';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import ProgressBar from 'ui/progress/progress-bar';

const FactionLoyaltyTaskRow = ({
  task,
}: FactionLoyaltyTaskRowProps): ReactNode => {
  const labelId = useId();

  const isComplete = task.current_amount >= task.required_amount;
  const valueLabel = `${task.current_amount} / ${task.required_amount}${isComplete ? ' (Complete)' : ''}`;

  return (
    <li>
      <ProgressBar
        label={resolveFactionLoyaltyTaskLabel(task)}
        aria_labelledby={labelId}
        value={task.current_amount}
        max={task.required_amount}
        value_label={valueLabel}
        variant={ProgressBarVariant.PRIMARY}
        size={ProgressBarSize.THIN}
      />
    </li>
  );
};

export default FactionLoyaltyTaskRow;
