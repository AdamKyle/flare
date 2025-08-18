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
    custom_message,
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
    if (custom_message) {
      return label;
    }

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

    const preferred: 'left' | 'right' | 'auto' =
      align === 'left' || align === 'right' ? align : 'auto';

    const viewportCap = Math.max(0, window.innerWidth - 48);
    const estimatedWidth = Math.min(448, viewportCap);

    const rect = buttonRef.current.getBoundingClientRect();
    const spaceLeft = rect.left;
    const spaceRight = window.innerWidth - rect.right;

    const hasRoomOnLeft = spaceLeft >= estimatedWidth;
    const hasRoomOnRight = spaceRight >= estimatedWidth;

    if (preferred === 'right') {
      if (hasRoomOnRight) {
        setPlacement('right');
        return;
      }

      if (hasRoomOnLeft) {
        setPlacement('left');
        return;
      }

      setPlacement(spaceRight >= spaceLeft ? 'right' : 'left');
      return;
    }

    if (preferred === 'left') {
      if (hasRoomOnLeft) {
        setPlacement('left');
        return;
      }

      if (hasRoomOnRight) {
        setPlacement('right');
        return;
      }

      setPlacement(spaceLeft >= spaceRight ? 'left' : 'right');
      return;
    }

    setPlacement(spaceRight >= spaceLeft ? 'right' : 'left');
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
