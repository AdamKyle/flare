import { useEventSystem } from 'event-system/hooks/use-event-system';

import GuideWalkthroughEventMap from '../events/guide-walkthrough-event-map';
import { GUIDE_WALKTHROUGH_EVENT_EMITTER } from '../events/guide-walkthrough-events';

export const useGuideWalkthroughEventEmitter = () => {
  const eventSystem = useEventSystem();

  return eventSystem.fetchOrCreateEventEmitter<GuideWalkthroughEventMap>(
    GUIDE_WALKTHROUGH_EVENT_EMITTER
  );
};
