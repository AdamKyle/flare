import clsx from 'clsx';
import React, { ReactNode, useRef } from 'react';
import { createPortal } from 'react-dom';

import useTooltipDisclosure from 'ui/tool-tips/hooks/use-tooltip-disclosure';
import useTooltipPlacement from 'ui/tool-tips/hooks/use-tooltip-placement';
import BaseToolTipProps from 'ui/tool-tips/types/base-tool-tips-props';

const isNodeInside = (
  element: HTMLElement | null,
  target: EventTarget | null
): boolean =>
  target instanceof Node && element !== null && element.contains(target);

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
    trigger,
    trigger_aria_label: triggerAriaLabel,
    placement = 'auto',
  } = props;

  const containerRef = useRef<HTMLSpanElement | null>(null);
  const buttonRef = useRef<HTMLButtonElement | null>(null);
  const popoverRef = useRef<HTMLDivElement | null>(null);

  const { open, openTip, closeTip, toggleTip } = useTooltipDisclosure({
    isOpenProp: is_open,
    onOpen: on_open,
    onClose: on_close,
  });

  const { position } = useTooltipPlacement({
    buttonRef,
    popoverRef,
    align,
    placement,
    open,
  });

  const handleTriggerPointerEnter = (event: React.PointerEvent): void => {
    if (event.pointerType === 'mouse') {
      openTip();
    }
  };

  const handleTriggerPointerLeave = (event: React.PointerEvent): void => {
    if (event.pointerType !== 'mouse') {
      return;
    }

    if (isNodeInside(popoverRef.current, event.relatedTarget)) {
      return;
    }

    closeTip();
  };

  const handlePopoverPointerLeave = (event: React.PointerEvent): void => {
    if (event.pointerType !== 'mouse') {
      return;
    }

    if (isNodeInside(containerRef.current, event.relatedTarget)) {
      return;
    }

    closeTip();
  };

  const handleKeyDown = (event: React.KeyboardEvent): void => {
    if (event.key === 'Escape') {
      closeTip();
    }
  };

  const handleBlur = (event: React.FocusEvent): void => {
    if (isNodeInside(containerRef.current, event.relatedTarget)) {
      return;
    }

    closeTip();
  };

  const contentClassName = clsx('leading-snug', {
    'text-base': size === 'md',
    'text-sm': size !== 'md',
  });

  const renderContentNode = (): ReactNode => {
    if (typeof content === 'string') {
      return <p className={contentClassName}>{content}</p>;
    }

    return <div className={contentClassName}>{content}</div>;
  };

  const renderTrigger = (): ReactNode => {
    if (trigger) {
      return (
        <button
          ref={buttonRef}
          type="button"
          aria-label={triggerAriaLabel ?? label}
          aria-expanded={open}
          aria-describedby={open ? tooltipId : undefined}
          onClick={toggleTip}
          className="focus-visible:ring-danube-500 dark:focus-visible:ring-danube-300 inline-flex items-center justify-center rounded focus:outline-none focus-visible:ring-2"
        >
          {trigger}
        </button>
      );
    }

    return (
      <button
        ref={buttonRef}
        type="button"
        aria-label={`Explain ${label}`}
        aria-expanded={open}
        aria-describedby={open ? tooltipId : undefined}
        onClick={toggleTip}
        className="mr-2 inline-flex h-7 w-7 items-center justify-center rounded text-gray-500 dark:text-gray-400"
      >
        <i className="fas fa-info-circle" aria-hidden="true" />
      </button>
    );
  };

  const renderPopover = (): ReactNode => {
    if (!open) {
      return null;
    }

    return createPortal(
      <div
        ref={popoverRef}
        id={tooltipId}
        role="tooltip"
        aria-live="polite"
        onPointerLeave={handlePopoverPointerLeave}
        onKeyDown={handleKeyDown}
        style={{
          top: position?.top ?? 0,
          left: position?.left ?? 0,
        }}
        className={clsx(
          'fixed z-[99999] rounded-md border bg-white p-3 shadow-lg',
          'w-max max-w-72 break-words whitespace-normal sm:max-w-md sm:min-w-64',
          'max-h-[min(70vh,28rem)] overflow-auto',
          'border-gray-200 text-gray-800',
          'dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100',
          { invisible: position === null }
        )}
      >
        {renderContentNode()}
      </div>,
      document.body
    );
  };

  return (
    <span
      ref={containerRef}
      className="relative inline-flex items-center"
      onPointerEnter={handleTriggerPointerEnter}
      onPointerLeave={handleTriggerPointerLeave}
      onKeyDown={handleKeyDown}
      onBlur={handleBlur}
    >
      {renderTrigger()}
      {renderPopover()}
    </span>
  );
};

export default BaseToolTip;
