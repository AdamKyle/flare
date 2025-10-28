import { ScreenMap } from './screen-map-type';
import { ScreenName } from './screen-name-type';

export type ScreenPropsOf<
  TMap extends ScreenMap,
  K extends ScreenName<TMap>,
> = TMap[K];
