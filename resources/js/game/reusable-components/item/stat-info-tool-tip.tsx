import clsx from 'clsx';
import React, { useId, useRef, useState } from 'react';

import StatInfoToolTipProps from './types/stat-info-tool-tip-props';
import { formatNumberWithCommas } from './utils/item-comparison';

type Horizontal = 'left' | 'right';
type Vertical = 'above' | 'below';

const StatInfoToolTip = (props: StatInfoToolTipProps) => {
  const {
    label,
    value,
    renderAsPercent,
    align = 'right',
    size = 'sm',
    is_open,
    on_open,
    on_close,
    custom_message,
  } = props;

  const localId = useId();
  const tooltipId = `stat-info-${label.replace(/\s+/g, '-').toLowerCase()}-${localId}`;

  const [internalOpen, setInternalOpen] = useState(false);
  const open = typeof is_open === 'boolean' ? is_open : internalOpen;

  const [horizontal, setHorizontal] = useState<Horizontal>(
    align === 'left' ? 'left' : 'right'
  );
  const [vertical, setVertical] = useState<Vertical>('below');

  const containerRef = useRef<HTMLSpanElement | null>(null);
  const buttonRef = useRef<HTMLButtonElement | null>(null);
  const popoverRef = useRef<HTMLDivElement | null>(null);

  const getDirection = (signedValue: number) => {
    if (signedValue > 0) {
      return 'increase';
    }
    if (signedValue < 0) {
      return 'decrease';
    }
    return 'no-change';
  };

  const getAmountText = (signedValue: number) => {
    const absoluteValue = Math.abs(signedValue);

    if (renderAsPercent || !Number.isInteger(signedValue)) {
      return `${(absoluteValue * 100).toFixed(2)}%`;
    }

    return formatNumberWithCommas(absoluteValue);
  };

  const getMessage = () => {
    if (custom_message) {
      return label;
    }

    const direction = getDirection(value);

    if (direction === 'no-change') {
      return `${label} will not change.`;
    }

    return `This will ${direction} your ${label} by ${getAmountText(value)}.`;
  };

  const getScrollParent = (node: HTMLElement | null): HTMLElement | null => {
    let element: HTMLElement | null = node ? node.parentElement : null;

    while (element) {
      const style = getComputedStyle(element);
      const overflowY = style.overflowY;
      const overflow = style.overflow;

      if (
        overflowY === 'auto' ||
        overflowY === 'scroll' ||
        overflow === 'auto' ||
        overflow === 'scroll'
      ) {
        return element;
      }

      element = element.parentElement;
    }

    return null;
  };

  const place = () => {
    const triggerEl = buttonRef.current;
    const tooltipEl = popoverRef.current;

    if (!triggerEl || !tooltipEl) {
      return;
    }

    const scrollParent = getScrollParent(containerRef.current);
    const parentRect = scrollParent
      ? scrollParent.getBoundingClientRect()
      : new DOMRect(0, 0, window.innerWidth, window.innerHeight);

    const triggerRect = triggerEl.getBoundingClientRect();

    const tooltipWidth = tooltipEl.offsetWidth || 256;
    const tooltipHeight = tooltipEl.offsetHeight || 120;

    const spaceRight = parentRect.right - triggerRect.right;
    const spaceLeft = triggerRect.left - parentRect.left;
    const spaceBelow = parentRect.bottom - triggerRect.bottom;
    const spaceAbove = triggerRect.top - parentRect.top;

    const preferRight = align !== 'left';

    if (preferRight && spaceRight >= tooltipWidth + 4) {
      setHorizontal('right');
    } else if (!preferRight && spaceLeft >= tooltipWidth + 4) {
      setHorizontal('left');
    } else if (spaceRight >= spaceLeft) {
      setHorizontal('right');
    } else {
      setHorizontal('left');
    }

    if (spaceBelow >= tooltipHeight + 4) {
      setVertical('below');
    } else if (spaceAbove >= tooltipHeight + 4) {
      setVertical('above');
    } else {
      setVertical(spaceBelow >= spaceAbove ? 'below' : 'above');
    }
  };

  const setOpen = (next: boolean) => {
    if (typeof is_open !== 'boolean') {
      setInternalOpen(next);
    }
  };

  const openTip = () => {
    setOpen(true);

    if (on_open) {
      on_open();
    }
  };

  const closeTip = () => {
    setOpen(false);

    if (on_close) {
      on_close();
    }
  };

  const toggleTip = () => {
    if (open) {
      closeTip();
      return;
    }

    openTip();
  };

  React.useLayoutEffect(() => {
    if (!open) {
      return;
    }

    place();

    const parent = getScrollParent(containerRef.current);
    const onScroll = () => place();
    const onResize = () => place();

    window.addEventListener('resize', onResize);
    parent?.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('scroll', onScroll, {
      passive: true,
      capture: true,
    });

    return () => {
      window.removeEventListener('resize', onResize);
      parent?.removeEventListener('scroll', onScroll);
      window.removeEventListener('scroll', onScroll, true);
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [open, align, label, value]);

  const handlePointerEnter = (event: React.PointerEvent) => {
    if (event.pointerType === 'mouse') {
      openTip();
    }
  };

  const handlePointerLeave = (event: React.PointerEvent) => {
    if (event.pointerType === 'mouse') {
      closeTip();
    }
  };

  const handleKeyDown = (event: React.KeyboardEvent) => {
    if (event.key === 'Escape') {
      closeTip();
    }
  };

  const handleBlur = (event: React.FocusEvent) => {
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

  const renderPopover = () => {
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
          'min-w-[16rem] max-w-[min(28rem,calc(100vw-3rem))] whitespace-normal break-words',
          'max-h-[min(70vh,28rem)] overflow-auto',
          'border-gray-200 text-gray-800',
          'dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100',
          horizontal === 'right' ? 'left-full ml-1' : 'right-full mr-1',
          vertical === 'below'
            ? 'top-0 translate-y-[-6px] origin-top'
            : 'bottom-full mb-1 origin-bottom'
        )}
      >
        <p
          className={clsx('leading-snug', {
            'text-base': size === 'md',
            'text-sm': size !== 'md',
          })}
        >
          {getMessage()}
        </p>
      </div>
    );
  };

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

export default StatInfoToolTip;
