import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useCallback, useEffect, useState } from 'react';

import { Traverse } from '../enums/traverse';
import UseEmitMapRefreshDefinition from './definitions/use-emit-map-refresh-definition';

export const useEmitMapRefresh = (): UseEmitMapRefreshDefinition => {
  const eventSystem = useEventSystem();

  const mapRefresherEmitter = eventSystem.fetchOrCreateEventEmitter<{
    [key: string]: boolean;
  }>(Traverse.REFRESH_MAP);

  const [shouldRefreshMap, setShouldRefreshMap] = useState(false);

  useEffect(() => {
    const updateShouldRefreshMap = (shouldRefresh: boolean) =>
      setShouldRefreshMap(shouldRefresh);

    mapRefresherEmitter.on(Traverse.REFRESH_MAP, updateShouldRefreshMap);

    return () => {
      mapRefresherEmitter.off(Traverse.REFRESH_MAP, updateShouldRefreshMap);
    };
  }, [mapRefresherEmitter]);

  const emitShouldRefreshMap = useCallback(
    (shouldRefresh: boolean) => {
      mapRefresherEmitter.emit(Traverse.REFRESH_MAP, shouldRefresh);
    },
    [mapRefresherEmitter]
  );

  return { shouldRefreshMap, emitShouldRefreshMap };
};
