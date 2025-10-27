import { ComponentType } from 'react';

import { Screens } from './screen-route-constants';
import CharacterSheet from '../../game/components/character-sheet/character-sheet';
import CharacterSheetProps from '../../game/components/character-sheet/types/character-sheet-props';

/**
 * Map of route names to their props types.
 * Add new routes here as you register more screens.
 */
export interface ScreenPropsMap {
  [Screens.CHARACTER_SHEET]: CharacterSheetProps;
}

export type ScreenName = keyof ScreenPropsMap;

export type ScreenPropsOf<K extends ScreenName> = ScreenPropsMap[K];

/**
 * Registry holds component constructors (not render functions).
 * This preserves the correlation name -> props at the type level.
 */
export const screenRegistry: {
  [K in ScreenName]: ComponentType<ScreenPropsMap[K]>;
} = {
  [Screens.CHARACTER_SHEET]: CharacterSheet,
};

/**
 * Typed helper to fetch the component for a route name with its exact props type.
 */
export function getScreenComponent<K extends ScreenName>(
  name: K
): ComponentType<ScreenPropsOf<K>> {
  return screenRegistry[name];
}
