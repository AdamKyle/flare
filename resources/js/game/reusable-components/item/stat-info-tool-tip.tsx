import clsx from 'clsx';
import React, { useId, useRef, useState } from 'react';

import StatInfoToolTipProps from './types/stat-info-tool-tip-props';
import { formatNumberWithCommas } from './utils/item-comparison';

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
  } = props;

  const localId = useId();
  const tooltipId = `stat-info-${label.replace(/\s+/g, '-').toLowerCase()}-${localId}`;

  const [internalOpen, setInternalOpen] = useState(false);
  const [placement, setPlacement] = useState<'left' | 'right'>(
    align === 'left' ? 'left' : 'right'
  );

  const open = typeof is_open === 'boolean' ? is_open : internalOpen;

  const containerRef = useRef<HTMLSpanElement | null>(null);
  const buttonRef = useRef<HTMLButtonElement | null>(null);

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
    const direction = getDirection(value);

    if (direction === 'no-change') {
      return `${label} will not change.`;
    }

    return `This will ${direction} your ${label} by ${getAmountText(value)}.`;
  };

  const getSizeClasses = () => {
    if (size === 'md') {
      return 'text-base';
    }

    return 'text-sm';
  };

  const computePlacement = () => {
    if (!buttonRef.current) {
      return;
    }

    // Preferred side from prop
    const preferred: 'left' | 'right' = align === 'left' ? 'left' : 'right';

    // Estimate width the bubble might need (matches CSS max width: 28rem,
    // but also caps to viewport minus margins so it never overflows).
    const viewportCap = Math.max(0, window.innerWidth - 48); // 24px margin on each side
    const estimatedWidth = Math.min(448 /* 28rem */, viewportCap);

    const rect = buttonRef.current.getBoundingClientRect();
    const spaceLeft = rect.left;
    const spaceRight = window.innerWidth - rect.right;

    const hasRoomOnLeft = spaceLeft >= estimatedWidth;
    const hasRoomOnRight = spaceRight >= estimatedWidth;

    // Choose preferred side if it has room, otherwise flip.
    if (preferred === 'right') {
      if (hasRoomOnRight) {
        setPlacement('right');
        return;
      }

      if (hasRoomOnLeft) {
        setPlacement('left');
        return;
      }

      // Neither side has full room: choose the side with more space.
      setPlacement(spaceRight >= spaceLeft ? 'right' : 'left');
      return;
    }

    // preferred === 'left'
    if (hasRoomOnLeft) {
      setPlacement('left');
      return;
    }

    if (hasRoomOnRight) {
      setPlacement('right');
      return;
    }

    setPlacement(spaceLeft >= spaceRight ? 'left' : 'right');
  };

  const openTip = () => {
    computePlacement();

    if (on_open) {
      on_open();
      return;
    }

    setInternalOpen(true);
  };

  const closeTip = () => {
    if (on_close) {
      on_close();
      return;
    }

    setInternalOpen(false);

    if (buttonRef.current) {
      buttonRef.current.focus();
    }
  };

  const toggleTip = () => {
    if (open) {
      closeTip();
      return;
    }

    openTip();
  };

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
        id={tooltipId}
        role="tooltip"
        aria-live="polite"
        className={clsx(
          'absolute top-0 translate-y-[-4px] z-50 rounded-md border bg-white p-3 shadow-lg',
          // Keep your exact width rules — no squishing, clamps on tiny screens
          'min-w-[16rem] max-w-[min(28rem,calc(100vw-3rem))] whitespace-normal break-words',
          'border-gray-200 text-gray-800',
          'dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100',
          placement === 'right'
            ? 'left-full ml-2 origin-left'
            : 'right-full mr-2 origin-right'
        )}
      >
        <p className={clsx('leading-snug', getSizeClasses())}>{getMessage()}</p>
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
          'text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500',
          'dark:text-gray-400 dark:hover:text-gray-200'
        )}
      >
        <i className="fas fa-info-circle" aria-hidden="true" />
      </button>

      {renderPopover()}
    </span>
  );
};

export default StatInfoToolTip;
