import { ComponentType } from 'react';

// Loosen the constraint so concrete maps with specific keys (no index signature)
// like AppScreenPropsMap satisfy the generic.
export type ScreenMap = object;

export type ScreenName<TMap extends ScreenMap> = Extract<keyof TMap, string>;
export type ScreenPropsOf<
  TMap extends ScreenMap,
  K extends ScreenName<TMap>,
> = TMap[K];

export type StackEntry<
  TMap extends ScreenMap,
  K extends ScreenName<TMap> = ScreenName<TMap>,
> = {
  name: K;
  props: ScreenPropsOf<TMap, K>;
  key: string;
};

export type ScreenRegistry<TMap extends ScreenMap> = {
  [K in ScreenName<TMap>]: ComponentType<TMap[K]>;
};

export type ScreenNavigation<TMap extends ScreenMap> = {
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
  _resolve: <K extends ScreenName<TMap>>(
    name: K
  ) => ComponentType<ScreenPropsOf<TMap, K>>;
};

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
