import { ReactNode } from 'react';
import CharacterSheetDefinition from "game-data/api-data-definitions/character/character-sheet-definition";

export default interface GoblinShopProviderProps {
  character: CharacterSheetDefinition;
  children: ReactNode;
  update_character: (character: Partial<CharacterSheetDefinition>) => void;
}
