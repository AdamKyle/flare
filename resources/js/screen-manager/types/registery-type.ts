import { ComponentType } from 'react';

import { ScreenMap } from './screen-map-type';
import { ScreenName } from './screen-name-type';

export type ScreenRegistry<TMap extends ScreenMap> = {
  [K in ScreenName<TMap>]: ComponentType<TMap[K]>;
};
