import AnnouncementMessageDefinition from '../../game/api-definitions/chat/annoucement-message-definition';
import CharacterSheetDefinition from '../api-data-definitions/character/character-sheet-definition';
import MonsterDefinition from '../api-data-definitions/monsters/monster-definition';

export default interface GameDataDefinition {
  character: CharacterSheetDefinition | null;
  monsters: MonsterDefinition[] | [];
  announcements: AnnouncementMessageDefinition[] | [];
  hasNewAnnouncements: boolean;
}
