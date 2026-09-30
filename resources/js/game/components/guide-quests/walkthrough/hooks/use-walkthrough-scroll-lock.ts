import { useEffect } from 'react';

const PAGE_SCROLL_KEYS = new Set([
  'ArrowDown',
  'ArrowUp',
  'End',
  'Home',
  'PageDown',
  'PageUp',
  ' ',
  'Spacebar',
]);

const INTERACTIVE_SELECTOR = [
  'a[href]',
  'button',
  'input',
  'select',
  'summary',
  'textarea',
  '[contenteditable="true"]',
  '[role="button"]',
  '[role="combobox"]',
  '[role="link"]',
  '[role="listbox"]',
  '[role="menuitem"]',
  '[role="option"]',
  '[role="slider"]',
  '[role="spinbutton"]',
  '[role="switch"]',
  '[role="tab"]',
  '[role="textbox"]',
  '[tabindex]:not([tabindex="-1"])',
].join(',');

const isInteractiveTarget = (target: EventTarget | null): boolean =>
  target instanceof Element && target.closest(INTERACTIVE_SELECTOR) !== null;

const findScrollableAncestor = (
  target: EventTarget | null
): HTMLElement | null => {
  if (!(target instanceof Element)) {
    return null;
  }

  let element = target instanceof HTMLElement ? target : target.parentElement;

  while (
    element !== null &&
    element !== document.body &&
    element !== document.documentElement
  ) {
    const isHiddenOrInert =
      element.closest('[inert]') !== null ||
      element.closest('[aria-hidden="true"]') !== null;

    if (isHiddenOrInert) {
      element = element.parentElement;

      continue;
    }

    const overflowY = window.getComputedStyle(element).overflowY;
    const canScroll =
      (overflowY === 'auto' || overflowY === 'scroll') &&
      element.scrollHeight > element.clientHeight;

    if (canScroll) {
      return element;
    }

    element = element.parentElement;
  }

  return null;
};

export const useWalkthroughScrollLock = (active: boolean): void => {
  useEffect(() => {
    if (!active) {
      return;
    }

    const preventManualScroll = (event: WheelEvent): void => {
      if (findScrollableAncestor(event.target) !== null) {
        return;
      }

      event.preventDefault();
    };

    const preventManualTouchScroll = (event: TouchEvent): void => {
      if (findScrollableAncestor(event.target) !== null) {
        return;
      }

      event.preventDefault();
    };

    const preventPageScrollKey = (event: KeyboardEvent): void => {
      if (!PAGE_SCROLL_KEYS.has(event.key)) {
        return;
      }

      if (isInteractiveTarget(event.target)) {
        return;
      }

      if (findScrollableAncestor(event.target) !== null) {
        return;
      }

      event.preventDefault();
    };

    window.addEventListener('wheel', preventManualScroll, {
      capture: true,
      passive: false,
    });
    window.addEventListener('touchmove', preventManualTouchScroll, {
      capture: true,
      passive: false,
    });
    window.addEventListener('keydown', preventPageScrollKey, true);

    return () => {
      window.removeEventListener('wheel', preventManualScroll, true);
      window.removeEventListener('touchmove', preventManualTouchScroll, true);
      window.removeEventListener('keydown', preventPageScrollKey, true);
    };
  }, [active]);
};
