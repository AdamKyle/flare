import { useCallback } from 'react';

import UseOpenActivitySidePeekDefinition from './definitions/use-open-activity-side-peek-definition';
import { SidePeekComponentRegistrationEnum } from '../../side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../side-peeks/base/hooks/use-side-peek-emitter';

export const useOpenActivitySidePeek =
  (): UseOpenActivitySidePeekDefinition => {
    const sidePeekEmitter = useSidePeekEmitter();

    const openActivity = useCallback(() => {
      sidePeekEmitter.emit(
        SidePeek.SIDE_PEEK,
        SidePeekComponentRegistrationEnum.ACTIVITY,
        {
          is_open: true,
          title: 'Activity',
          allow_clicking_outside: true,
        }
      );
    }, [sidePeekEmitter]);

    return { open_activity: openActivity };
  };
