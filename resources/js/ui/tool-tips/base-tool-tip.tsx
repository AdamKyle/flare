import clsx from 'clsx';
import React, { useCallback, useRef } from 'react';

import useTooltipDisclosure from 'ui/tool-tips/hooks/use-tooltip-disclosure';
import useTooltipPlacement from 'ui/tool-tips/hooks/use-tooltip-placement';
import BaseToolTipProps from 'ui/tool-tips/types/base-tool-tips-props';

const BaseToolTip = (props: BaseToolTipProps) => {
  const {
    tooltipId,
    label,
    align = 'right',
    size = 'sm',
    is_open,
    on_open,
    on_close,
    content,
    placementDeps,
  } = props;

  const containerRef = useRef<HTMLSpanElement | null>(null);
  const buttonRef = useRef<HTMLButtonElement | null>(null);
  const popoverRef = useRef<HTMLDivElement | null>(null);

  const { open, openTip, closeTip, toggleTip } = useTooltipDisclosure({
    isOpenProp: is_open,
    onOpen: on_open,
    onClose: on_close,
  });

  const { horizontal, vertical } = useTooltipPlacement({
    containerRef,
    buttonRef,
    popoverRef,
    align,
    open,
    extraDeps: placementDeps,
  });

  const handlePointerEnter = (event: React.PointerEvent): void => {
    if (event.pointerType === 'mouse') {
      openTip();
    }
  };

  const handlePointerLeave = (event: React.PointerEvent): void => {
    if (event.pointerType === 'mouse') {
      closeTip();
    }
  };

  const handleKeyDown = (event: React.KeyboardEvent): void => {
    if (event.key === 'Escape') {
      closeTip();
    }
  };

  const handleBlur = (event: React.FocusEvent): void => {
    const nextTarget = event.relatedTarget as Node | null;

    if (
      containerRef.current &&
      nextTarget &&
      containerRef.current.contains(nextTarget)
    ) {
      return;
    }

    closeTip();
  };

  const renderContentNode = useCallback(() => {
    if (!open) {
      return null;
    }

    const isString = typeof content === 'string';

    if (isString) {
      return (
        <p
          className={clsx('leading-snug', {
            'text-base': size === 'md',
            'text-sm': size !== 'md',
          })}
        >
          {content as string}
        </p>
      );
    }

    return (
      <div
        className={clsx('leading-snug', {
          'text-base': size === 'md',
          'text-sm': size !== 'md',
        })}
      >
        {content as React.ReactNode}
      </div>
    );
  }, [open, content, size]);

  const renderPopover = useCallback(() => {
    if (!open) {
      return null;
    }

    return (
      <div
        ref={popoverRef}
        id={tooltipId}
        role="tooltip"
        aria-live="polite"
        className={clsx(
          'absolute z-50 rounded-md border bg-white p-3 shadow-lg',
          'max-w-[min(28rem,calc(100vw-3rem))] min-w-[16rem] break-words whitespace-normal',
          'max-h-[min(70vh,28rem)] overflow-auto',
          'border-gray-200 text-gray-800',
          'dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100',
          horizontal === 'right' ? 'left-full ml-1' : 'right-full mr-1',
          vertical === 'below'
            ? 'top-0 origin-top translate-y-[-6px]'
            : 'bottom-full mb-1 origin-bottom'
        )}
      >
        {renderContentNode()}
      </div>
    );
  }, [open, tooltipId, horizontal, vertical, renderContentNode]);

  return (
    <span
      ref={containerRef}
      className="relative inline-flex items-center"
      onPointerEnter={handlePointerEnter}
      onPointerLeave={handlePointerLeave}
      onKeyDown={handleKeyDown}
      onBlur={handleBlur}
    >
      <button
        ref={buttonRef}
        type="button"
        aria-label={`Explain ${label}`}
        aria-expanded={open}
        aria-describedby={open ? tooltipId : undefined}
        onClick={toggleTip}
        className={clsx(
          'mr-2 inline-flex h-7 w-7 items-center justify-center rounded',
          'text-gray-500',
          'dark:text-gray-400'
        )}
      >
        <i className="fas fa-info-circle" aria-hidden="true" />
      </button>

      {renderPopover()}
    </span>
  );
};

export default BaseToolTip;
