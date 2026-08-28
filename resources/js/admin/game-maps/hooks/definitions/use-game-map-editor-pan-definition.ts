import { PointerEvent as ReactPointerEvent } from 'react';

import CoordinateDefinition from '../../types/coordinate-definition';

export default interface UseGameMapEditorPanDefinition {
  translate: { x: number; y: number };
  reset_translate: () => void;
  pan_into_view: (coordinate: CoordinateDefinition) => void;
  handle_pointer_down: (event: ReactPointerEvent<HTMLDivElement>) => void;
  handle_pointer_move: (event: ReactPointerEvent<HTMLDivElement>) => void;
  end_drag: (event: ReactPointerEvent<HTMLDivElement>) => boolean;
  handle_pointer_cancel: (event: ReactPointerEvent<HTMLDivElement>) => void;
  handle_lost_pointer_capture: () => void;
}
