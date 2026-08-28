import {
  PointerEvent as ReactPointerEvent,
  RefObject,
  useCallback,
  useRef,
  useState,
} from 'react';

import UseGameMapEditorPanDefinition from './definitions/use-game-map-editor-pan-definition';
import { GameMapEditorValues } from '../enums/game-map-editor-values';
import CoordinateDefinition from '../types/coordinate-definition';
import GameMapEditorDragState from '../types/game-map-editor-drag-state';
import { clampMapAxis } from '../utils/clamp-map-axis';

export const useGameMapEditorPan = (
  containerRef: RefObject<HTMLDivElement | null>,
  mapWidth: number,
  mapHeight: number
): UseGameMapEditorPanDefinition => {
  const dragRef = useRef<GameMapEditorDragState | null>(null);
  const [translate, setTranslate] = useState({ x: 0, y: 0 });

  const resetTranslate = useCallback((): void => {
    setTranslate({ x: 0, y: 0 });
  }, []);

  const panIntoView = (coordinate: CoordinateDefinition): void => {
    const container = containerRef.current;

    if (!container) {
      return;
    }

    const viewportWidth = container.clientWidth;
    const viewportHeight = container.clientHeight;

    setTranslate((previous) => {
      let nextX = previous.x;
      let nextY = previous.y;

      const cellScreenLeft = coordinate.left + previous.x;
      const cellScreenRight = coordinate.left + coordinate.width + previous.x;

      if (cellScreenLeft < 0) {
        nextX = previous.x - cellScreenLeft;
      } else if (cellScreenRight > viewportWidth) {
        nextX = previous.x - (cellScreenRight - viewportWidth);
      }

      const cellScreenTop = coordinate.top + previous.y;
      const cellScreenBottom = coordinate.top + coordinate.height + previous.y;

      if (cellScreenTop < 0) {
        nextY = previous.y - cellScreenTop;
      } else if (cellScreenBottom > viewportHeight) {
        nextY = previous.y - (cellScreenBottom - viewportHeight);
      }

      return {
        x: clampMapAxis(nextX, mapWidth, viewportWidth),
        y: clampMapAxis(nextY, mapHeight, viewportHeight),
      };
    });
  };

  const handlePointerDown = (
    event: ReactPointerEvent<HTMLDivElement>
  ): void => {
    event.currentTarget.setPointerCapture(event.pointerId);

    dragRef.current = {
      pointer_id: event.pointerId,
      start_client_x: event.clientX,
      start_client_y: event.clientY,
      start_translate_x: translate.x,
      start_translate_y: translate.y,
      is_panning: false,
    };
  };

  const handlePointerMove = (
    event: ReactPointerEvent<HTMLDivElement>
  ): void => {
    const drag = dragRef.current;

    if (!drag || drag.pointer_id !== event.pointerId) {
      return;
    }

    const deltaX = event.clientX - drag.start_client_x;
    const deltaY = event.clientY - drag.start_client_y;

    if (
      !drag.is_panning &&
      Math.hypot(deltaX, deltaY) > GameMapEditorValues.PanThreshold
    ) {
      drag.is_panning = true;
    }

    if (!drag.is_panning) {
      return;
    }

    const container = containerRef.current;

    if (!container) {
      return;
    }

    setTranslate({
      x: clampMapAxis(
        drag.start_translate_x + deltaX,
        mapWidth,
        container.clientWidth
      ),
      y: clampMapAxis(
        drag.start_translate_y + deltaY,
        mapHeight,
        container.clientHeight
      ),
    });
  };

  const endDrag = (event: ReactPointerEvent<HTMLDivElement>): boolean => {
    const drag = dragRef.current;

    if (!drag || drag.pointer_id !== event.pointerId) {
      return false;
    }

    if (event.currentTarget.hasPointerCapture(event.pointerId)) {
      event.currentTarget.releasePointerCapture(event.pointerId);
    }

    dragRef.current = null;

    return drag.is_panning;
  };

  const handlePointerCancel = (
    event: ReactPointerEvent<HTMLDivElement>
  ): void => {
    if (event.currentTarget.hasPointerCapture(event.pointerId)) {
      event.currentTarget.releasePointerCapture(event.pointerId);
    }

    dragRef.current = null;
  };

  const handleLostPointerCapture = (): void => {
    dragRef.current = null;
  };

  return {
    translate,
    reset_translate: resetTranslate,
    pan_into_view: panIntoView,
    handle_pointer_down: handlePointerDown,
    handle_pointer_move: handlePointerMove,
    end_drag: endDrag,
    handle_pointer_cancel: handlePointerCancel,
    handle_lost_pointer_capture: handleLostPointerCapture,
  };
};
