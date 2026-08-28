import { ComponentType } from 'react';

import { GameMapScreens } from './game-map-screen-constants';
import {
  GameMapScreenName,
  GameMapScreenPropsMap,
} from './game-map-screen-props';
import GameMapEditorScreen from '../screens/game-map-editor-screen';
import GameMapFormScreen from '../screens/game-map-form-screen';
import GameMapListScreen from '../screens/game-map-list-screen';
import GameMapShowScreen from '../screens/game-map-show-screen';

export const gameMapScreenRegistry: {
  [K in GameMapScreenName]: ComponentType<GameMapScreenPropsMap[K]>;
} = {
  [GameMapScreens.LIST]: GameMapListScreen,
  [GameMapScreens.SHOW]: GameMapShowScreen,
  [GameMapScreens.EDITOR]: GameMapEditorScreen,
  [GameMapScreens.FORM]: GameMapFormScreen,
};
