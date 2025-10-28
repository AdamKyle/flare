import { ComponentType } from 'react';

import { ScreenMap } from './screen-map-type';
import { ScreenName } from './screen-name-type';
import { ScreenPropsOf } from './screen-props-of-type';
import { StackEntry } from './stack-entry-type';

export interface ScreenNavigation<TMap extends ScreenMap> {
  navigateTo: <K extends ScreenName<TMap>>(
    name: K,
    props: ScreenPropsOf<TMap, K>
  ) => void;

  replaceWith: <K extends ScreenName<TMap>>(
    name: K,
    props: ScreenPropsOf<TMap, K>
  ) => void;

  resetTo: <K extends ScreenName<TMap>>(
    name: K,
    props: ScreenPropsOf<TMap, K>
  ) => void;

  pop: (count?: number) => void;

  stackDepth: number;

  _getTop: () => StackEntry<TMap> | null;
  _getHidden: () => StackEntry<TMap>[];

  // Must return the component type that accepts the mapped props for K.
  _resolve: <K extends ScreenName<TMap>>(
    name: K
  ) => ComponentType<ScreenPropsOf<TMap, K>>;
}
