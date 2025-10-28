import { ScreenMap } from './screen-map-type';

export type ScreenName<TMap extends ScreenMap> = Extract<keyof TMap, string>;
