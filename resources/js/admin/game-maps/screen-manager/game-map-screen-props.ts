import { GameMapScreens } from './game-map-screen-constants';
import { GameMapFormScreenProps } from '../types/game-map-form-screen-props';
import GameMapEditorScreenProps from '../types/game-map-editor-screen-props';

export type GameMapListScreenProps = Record<string, never>;

export interface GameMapShowScreenProps {
  game_map_id: number;
}

export interface GameMapScreenPropsMap {
  [GameMapScreens.LIST]: GameMapListScreenProps;
  [GameMapScreens.SHOW]: GameMapShowScreenProps;
  [GameMapScreens.EDITOR]: GameMapEditorScreenProps;
  [GameMapScreens.FORM]: GameMapFormScreenProps;
}

export type GameMapScreenName = keyof GameMapScreenPropsMap;
export type GameMapScreenPropsOf<K extends GameMapScreenName> =
  GameMapScreenPropsMap[K];
