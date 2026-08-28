import { KeyboardEvent as ReactKeyboardEvent } from 'react';

export default interface GameMapKeyboardNavigationDefinition {
  handle_key_down: (event: ReactKeyboardEvent<HTMLDivElement>) => void;
}
