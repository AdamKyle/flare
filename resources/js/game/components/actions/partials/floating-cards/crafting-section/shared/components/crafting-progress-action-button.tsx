import React, { ReactNode } from 'react';

import CraftingProgressActionButtonProps from './types/crafting-progress-action-button-props';

import ProgressButton from 'ui/buttons/button-progress';

const clampProgress = (progress: number): number => {
  return Math.min(Math.max(progress, 0), 100);
};

const getButtonLabel = ({
  idle_label,
  submitting_label,
  timeout_label,
  submitting,
  is_timeout_active,
  formatted_remaining,
}: Pick<
  CraftingProgressActionButtonProps,
  | 'idle_label'
  | 'submitting_label'
  | 'timeout_label'
  | 'submitting'
  | 'is_timeout_active'
  | 'formatted_remaining'
>): string => {
  if (submitting) {
    return submitting_label;
  }

  if (is_timeout_active) {
    return `${timeout_label} in ${formatted_remaining}`;
  }

  return idle_label;
};

const getDisplayedProgress = (
  submitting: boolean,
  is_timeout_active: boolean,
  progress: number
): number => {
  if (submitting) {
    return 100;
  }

  if (is_timeout_active) {
    return clampProgress(progress);
  }

  return 0;
};

const CraftingProgressActionButton = ({
  idle_label,
  submitting_label,
  timeout_label,
  submitting,
  is_timeout_active,
  progress,
  formatted_remaining,
  disabled,
  on_click,
  variant,
  additional_css,
}: CraftingProgressActionButtonProps): ReactNode => {
  const label = getButtonLabel({
    idle_label,
    submitting_label,
    timeout_label,
    submitting,
    is_timeout_active,
    formatted_remaining,
  });

  const displayedProgress = getDisplayedProgress(
    submitting,
    is_timeout_active,
    progress
  );

  const isDisabled = submitting || is_timeout_active || disabled;

  return (
    <ProgressButton
      label={label}
      on_click={on_click}
      variant={variant}
      progress={displayedProgress}
      disabled={isDisabled}
      additional_css={additional_css}
      aria_busy={submitting}
    />
  );
};

export default CraftingProgressActionButton;
