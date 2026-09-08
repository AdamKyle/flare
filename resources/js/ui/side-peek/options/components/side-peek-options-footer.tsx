import clsx from 'clsx';
import React, { ReactNode } from 'react';

import LoadingButton from 'ui/buttons/loading-button';
import SidePeekOptionsFooterProps from 'ui/side-peek/options/types/side-peek-options-footer-props';

const SidePeekOptionsFooter = (
  props: SidePeekOptionsFooterProps
): ReactNode => {
  if (props.options.length === 0) {
    return null;
  }

  const isAnyOptionLoading = props.options.some((option) => option.loading);

  return (
    <div className="flex flex-col bg-gray-50 p-4 dark:bg-gray-900">
      <div className="flex flex-col sm:flex-row sm:items-center sm:gap-2">
        {props.options.map((option, index) => (
          <LoadingButton
            key={option.id}
            label={option.label}
            loading_label={option.loading_label ?? 'Working...'}
            aria_label={option.aria_label}
            variant={option.variant}
            on_click={option.on_click}
            disabled={option.disabled || isAnyOptionLoading}
            is_loading={Boolean(option.loading)}
            additional_css={clsx(
              'w-full sm:flex-1',
              index > 0 && 'mt-2 sm:mt-0'
            )}
          />
        ))}
      </div>
    </div>
  );
};

export default SidePeekOptionsFooter;
