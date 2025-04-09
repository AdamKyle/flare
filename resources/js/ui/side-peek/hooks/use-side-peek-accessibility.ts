import React, { useRef } from 'react';
import type { RefObject } from 'react';

interface SidePeekAccessibilityProps {
  is_open: boolean;
  allow_clicking_outside?: boolean;
  on_close?: () => void;
}

interface SidePeekAccessibilityDefinition {
  dialogRef: RefObject<HTMLDivElement | null>;
  handleKeyDown: (e: React.KeyboardEvent<HTMLDivElement>) => void;
  handleClickingOutside: () => void;
}

export const useSidePeekAccessibility = ({
  is_open,
  allow_clicking_outside,
  on_close,
}: SidePeekAccessibilityProps): SidePeekAccessibilityDefinition => {
  const dialogRef = useRef<HTMLDivElement>(null);

  if (is_open && dialogRef.current) {
    dialogRef.current.focus();
  }

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
