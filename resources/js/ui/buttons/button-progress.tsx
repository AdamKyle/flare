import clsx from 'clsx';
import React, { ReactNode } from 'react';

import { baseStyles } from 'ui/buttons/styles/button/base-styles';
import { variantStyles } from 'ui/buttons/styles/button/variant-styles';
import { progressBaseStyles } from 'ui/buttons/styles/button-progress/progress-base-styles';
import { progressVariantStyles } from 'ui/buttons/styles/button-progress/progress-variant-styles';
import ProgressButtonProps from 'ui/buttons/types/progress-button-props';

const ProgressButton = (props: ProgressButtonProps): ReactNode => {
  return (
    <button
      onClick={props.on_click}
      className={clsx(
        baseStyles(),
        variantStyles(props.variant),
        props.additional_css
      )}
      aria-label={props.label}
      aria-valuenow={props.progress}
      aria-valuemin={0}
      aria-valuemax={100}
      style={{ position: 'relative' }}
    >
      <span className="relative z-10">{props.label}</span>
      <div className="absolute bottom-0 left-0 h-full w-full rounded-lg bg-white/20">
        <div
          className={clsx(
            progressBaseStyles(),
            progressVariantStyles(props.variant)
          )}
          style={{ width: `${props.progress}%` }}
        ></div>
      </div>
    </button>
  );
};

export default ProgressButton;
