import { ComponentType } from 'react';

import { Screens } from './screen-manager-constants';
import { AppScreenPropsMap, AppScreenName } from './screen-manager-props';
import CharacterSheet from '../../game/components/character-sheet/character-sheet';

export const appScreenRegistry: {
  [K in AppScreenName]: ComponentType<AppScreenPropsMap[K]>;
} = {
  [Screens.CHARACTER_SHEET]: CharacterSheet,
};
