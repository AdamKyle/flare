import { useCallback, useEffect, useRef } from 'react';

import UseGuideWalkthroughEventAdvancementDefinition from './definitions/use-guide-walkthrough-event-advancement-definition';
import { useGuideWalkthroughEventEmitter } from './use-guide-walkthrough-event-emitter';
import { GuideWalkthroughEvent } from '../enums/guide-walkthrough-event';

export const useGuideWalkthroughEventAdvancement =
  (): UseGuideWalkthroughEventAdvancementDefinition => {
    const eventEmitter = useGuideWalkthroughEventEmitter();

    const eventNameRef = useRef<GuideWalkthroughEvent | null>(null);
    const eventHandlerRef = useRef<(() => void) | null>(null);

    const unbindStepEvent = useCallback((): void => {
      if (eventNameRef.current && eventHandlerRef.current) {
        eventEmitter.off(eventNameRef.current, eventHandlerRef.current);
      }

      eventNameRef.current = null;
      eventHandlerRef.current = null;
    }, [eventEmitter]);

    const bindStepEvent = useCallback(
      (event: GuideWalkthroughEvent, onEvent: () => void): void => {
        unbindStepEvent();

        eventNameRef.current = event;
        eventHandlerRef.current = onEvent;
        eventEmitter.on(event, onEvent);
      },
      [eventEmitter, unbindStepEvent]
    );

    useEffect(() => unbindStepEvent, [unbindStepEvent]);

    return { bindStepEvent, unbindStepEvent };
  };
