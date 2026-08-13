import React, { useEffect, useRef } from 'react';

import UseStackedCardAccessibilityDefinition from './definitions/use-stacked-card-accessibility-definition';
import UseStackedCardAccessibilityParams from './definitions/use-stacked-card-accessibility-params';

const FOCUSABLE_SELECTOR =
  'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';

export const useStackedCardAccessibility = ({
  active,
  on_close,
}: UseStackedCardAccessibilityParams): UseStackedCardAccessibilityDefinition => {
  const dialogRef = useRef<HTMLDivElement>(null);
  const previouslyFocusedElementRef = useRef<HTMLElement | null>(null);

  useEffect(() => {
    if (!active) {
      return;
    }

    previouslyFocusedElementRef.current =
      document.activeElement instanceof HTMLElement
        ? document.activeElement
        : null;

    dialogRef.current?.focus();

    return () => {
      const previouslyFocusedElement = previouslyFocusedElementRef.current;

      if (
        previouslyFocusedElement &&
        document.contains(previouslyFocusedElement)
      ) {
        previouslyFocusedElement.focus();
      }
    };
  }, [active]);

  const getFocusableElements = (): HTMLElement[] => {
    if (!dialogRef.current) {
      return [];
    }

    return Array.from(
      dialogRef.current.querySelectorAll<HTMLElement>(FOCUSABLE_SELECTOR)
    );
  };

  const handleTabKey = (event: React.KeyboardEvent<HTMLDivElement>) => {
    const focusableElements = getFocusableElements();

    if (focusableElements.length === 0) {
      event.preventDefault();
      dialogRef.current?.focus();

      return;
    }

    const firstElement = focusableElements[0];
    const lastElement = focusableElements[focusableElements.length - 1];
    const activeElement = document.activeElement;

    if (event.shiftKey) {
      if (
        activeElement === firstElement ||
        activeElement === dialogRef.current
      ) {
        event.preventDefault();
        lastElement.focus();
      }

      return;
    }

    if (activeElement === lastElement) {
      event.preventDefault();
      firstElement.focus();
    }
  };

  const handleKeyDown = (event: React.KeyboardEvent<HTMLDivElement>) => {
    if (event.key === 'Escape') {
      on_close();

      return;
    }

    if (event.key === 'Tab') {
      handleTabKey(event);
    }
  };

  return {
    dialogRef,
    handleKeyDown,
  };
};
