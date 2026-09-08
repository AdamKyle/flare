import { QuestKind } from '../../../../../../reusable-components/quest/enums/quest-kind';

export default interface UseCharacterQuestTreeParams {
  characterId: number;
  mapId: number | null;
  kind: QuestKind | null;
}
