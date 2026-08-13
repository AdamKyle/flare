import type { KeyboardEvent, RefObject } from 'react';

export default interface UseStackedCardAccessibilityDefinition {
  dialogRef: RefObject<HTMLDivElement | null>;
  handleKeyDown: (event: KeyboardEvent<HTMLDivElement>) => void;
}
