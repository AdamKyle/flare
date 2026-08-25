import React, { useEffect, useRef } from 'react';
import type { RefObject } from 'react';

interface SidePeekAccessibilityProps {
  active: boolean;
  allow_clicking_outside?: boolean;
  on_close?: () => void;
}

interface SidePeekAccessibilityDefinition {
  dialogRef: RefObject<HTMLDivElement | null>;
  handleKeyDown: (e: React.KeyboardEvent<HTMLDivElement>) => void;
  handleClickingOutside: () => void;
}

export const useSidePeekAccessibility = ({
  active,
  allow_clicking_outside,
  on_close,
}: SidePeekAccessibilityProps): SidePeekAccessibilityDefinition => {
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

    dialogRef.current?.focus({ preventScroll: true });

    return () => {
      const previouslyFocusedElement = previouslyFocusedElementRef.current;

      if (
        previouslyFocusedElement &&
        document.contains(previouslyFocusedElement)
      ) {
        previouslyFocusedElement.focus({ preventScroll: true });
      }
    };
  }, [active]);

  const handleKeyDown = (e: React.KeyboardEvent<HTMLDivElement>) => {
    if (e.key === 'Escape' && on_close) {
      on_close();
    }
  };

  const handleClickingOutside = () => {
    if (allow_clicking_outside && on_close) {
      on_close();
    }
  };

  return {
    dialogRef,
    handleKeyDown,
    handleClickingOutside,
  };
};
