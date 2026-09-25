import AnnouncementMessageDefinition from '../../game/api-definitions/chat/annoucement-message-definition';
import ExplorationOutputResponseDefinition from '../../game/components/actions/partials/monster-section/exploration/types/exploration-output-response-definition';
import CharacterSheetDefinition from '../api-data-definitions/character/character-sheet-definition';
import MonsterDefinition from '../api-data-definitions/monsters/monster-definition';

export interface BattleRewardProgressionDefinition {
  level: number;
  xp: number;
  xp_next: number;
}

export default interface GameDataDefinition {
  character: CharacterSheetDefinition | null;
  monsters: MonsterDefinition[] | [];
  announcements: AnnouncementMessageDefinition[] | [];
  hasNewAnnouncements: boolean;
  explorationOutput: ExplorationOutputResponseDefinition | null;
  battleRewardProgression: BattleRewardProgressionDefinition | null;
}
