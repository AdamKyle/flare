import React, { ReactNode } from 'react';

import SignedAdjustmentProps from '../types/partials/signed-adjustment-props';

const resolveIconClassName = (value: number): string => {
  if (value > 0) {
    return 'fas fa-chevron-up text-emerald-600 dark:text-emerald-400';
  }

  if (value < 0) {
    return 'fas fa-chevron-down text-rose-600 dark:text-rose-400';
  }

  return 'fas fa-minus text-gray-500 dark:text-gray-400';
};

const resolveValueClassName = (value: number): string => {
  if (value > 0) {
    return 'text-emerald-600 dark:text-emerald-400';
  }

  if (value < 0) {
    return 'text-rose-600 dark:text-rose-400';
  }

  return 'text-gray-700 dark:text-gray-300';
};

const SignedAdjustment = ({
  value,
  display_text,
  screen_reader_text,
}: SignedAdjustmentProps): ReactNode => (
  <span className="flex items-center justify-end gap-1 tabular-nums">
    <i className={resolveIconClassName(value)} aria-hidden="true" />
    <span className={resolveValueClassName(value)} aria-hidden="true">
      {display_text}
    </span>
    <span className="sr-only">{screen_reader_text}</span>
  </span>
);

export default SignedAdjustment;
