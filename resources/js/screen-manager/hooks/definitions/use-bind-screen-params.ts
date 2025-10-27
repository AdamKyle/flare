import {
  BindMode,
  ScreenMap,
  ScreenName,
  ScreenPropsOf,
} from '../../core/types';

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

export type { ScreenMap, ScreenName, ScreenPropsOf };
