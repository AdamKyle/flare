import { ScreenMap } from '../../types/screen-map-type';
import { ScreenName } from '../../types/screen-name-type';
import { ScreenPropsOf } from '../../types/screen-props-of-type';

export type BindMode = 'push' | 'replace' | 'reset';

export default interface UseBindScreenParams<
  TMap extends ScreenMap,
  K extends ScreenName<TMap>,
> {
  when: boolean;
  to: K;
  props: () => ScreenPropsOf<TMap, K>;
  mode?: BindMode;
  dedupeKey?: string;
}
