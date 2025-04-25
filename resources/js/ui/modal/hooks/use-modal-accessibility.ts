import React, { useEffect, useRef } from 'react';
import type { RefObject } from 'react';

interface ModalAccessibilityProps {
  is_open: boolean;
  allow_clicking_outside?: boolean;
  on_close?: () => void;
}

interface ModalAccessibilityDefinition {
  dialogRef: RefObject<HTMLDivElement | null>;
  handleKeyDown: (e: React.KeyboardEvent<HTMLDivElement>) => void;
  handleClickingOutside: (e: React.MouseEvent<HTMLDivElement>) => void;
}

export const useModalAccessibility = ({
  is_open,
  allow_clicking_outside,
  on_close,
}: ModalAccessibilityProps): ModalAccessibilityDefinition => {
  const dialogRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (is_open && dialogRef.current) {
      dialogRef.current.focus();
    }
  }, [is_open]);

  const handleKeyDown = (e: React.KeyboardEvent<HTMLDivElement>) => {
    if (e.key === 'Escape' && on_close) {
      on_close();
    }
  };

  const handleClickingOutside = (e: React.MouseEvent<HTMLDivElement>) => {
    if (allow_clicking_outside && e.target === e.currentTarget && on_close) {
      on_close();
    }
  };

  return {
    dialogRef,
    handleKeyDown,
    handleClickingOutside,
  };
};
