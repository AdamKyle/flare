import clsx from 'clsx';
import React, { ReactNode, useRef } from 'react';

import { baseStyles } from 'ui/buttons/styles/button/base-styles';
import { variantStyles } from 'ui/buttons/styles/button/variant-styles';
import { progressBaseStyles } from 'ui/buttons/styles/button-progress/progress-base-styles';
import { progressVariantStyles } from 'ui/buttons/styles/button-progress/progress-variant-styles';
import ProgressButtonProps from 'ui/buttons/types/progress-button-props';

const ProgressButton = (props: ProgressButtonProps): ReactNode => {
  const prevProgressRef = useRef(props.progress);
  const isCountingDown = props.progress <= prevProgressRef.current;
  prevProgressRef.current = props.progress;

  const fillColorClass =
    props.progress_fill_class ?? progressVariantStyles(props.variant);

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
      disabled={props.disabled}
      type="button"
      style={{ position: 'relative' }}
    >
      <span className="relative z-10">{props.label}</span>
      <div className="absolute bottom-0 left-0 h-full w-full rounded-lg bg-white/20">
        <div
          className={clsx(
            progressBaseStyles(),
            fillColorClass,
            isCountingDown && 'transition-all duration-700'
          )}
          style={{ width: `${props.progress}%` }}
        ></div>
      </div>
    </button>
  );
};

export default ProgressButton;
