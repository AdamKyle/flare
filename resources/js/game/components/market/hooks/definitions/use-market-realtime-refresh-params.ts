import { StateSetter } from '../../../../../types/state-setter-type';

export default interface UseMarketRealtimeRefreshParams {
  realtime_version: number;
  set_page: StateSetter<number>;
  set_refresh: StateSetter<boolean>;
}
