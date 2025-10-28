import { ScreenMap } from './screen-map-type';
import { ScreenName } from './screen-name-type';
import { ScreenPropsOf } from './screen-props-of-type';

export interface StackEntry<
  TMap extends ScreenMap,
  K extends ScreenName<TMap> = ScreenName<TMap>,
> {
  name: K;
  props: ScreenPropsOf<TMap, K>;
  key: string;
}
