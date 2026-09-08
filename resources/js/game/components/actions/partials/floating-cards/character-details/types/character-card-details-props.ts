import ActiveBoonDefinition from '../../crafting-section/sections/alchemy/active-boons/api/definitions/active-boon-definition';

import CharacterSheetDefinition from 'game-data/api-data-definitions/character/character-sheet-definition';

export default interface CharacterCardDetailsProps {
  characterData: CharacterSheetDefinition;
  active_boons: ActiveBoonDefinition[];
  active_boons_loading: boolean;
  on_open_active_boons: () => void;
  on_active_boons_complete: () => void;
}
