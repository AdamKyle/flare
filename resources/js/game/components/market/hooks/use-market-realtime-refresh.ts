import { useEffect } from 'react';

import UseMarketRealtimeRefreshParams from './definitions/use-market-realtime-refresh-params';

export const useMarketRealtimeRefresh = ({
  realtime_version,
  set_page,
  set_refresh,
}: UseMarketRealtimeRefreshParams): void => {
  useEffect(() => {
    if (realtime_version === 0) {
      return;
    }

    set_page(1);
    set_refresh((previousRefresh) => !previousRefresh);
  }, [realtime_version, set_page, set_refresh]);
};
