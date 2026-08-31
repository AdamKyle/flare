import React from 'react';

import createScreenManager from 'screen-manager/create-screen-manager';

import { GameMapScreenPropsMap } from './game-map-screen-props';
import { gameMapScreenRegistry } from './game-map-screen-registry';

const GameMapScreenKit = createScreenManager<GameMapScreenPropsMap>();

export const {
  ScreenManagerProvider: GameMapScreenManagerProvider,
  useScreenNavigation: useGameMapScreenNavigation,
  ScreenHost: GameMapScreenHost,
} = GameMapScreenKit;

export const GameMapScreenProvider = (props: {
  children?: React.ReactNode;
}) => {
  return (
    <GameMapScreenManagerProvider registry={gameMapScreenRegistry}>
      {props.children}
    </GameMapScreenManagerProvider>
  );
};
