import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import QuestDetailDefinition from '../../../../../../reusable-components/quest/api/definitions/quest-detail-definition';
import QuestItemOwnershipState from '../../../../../side-peeks/components/items/enums/quest-item-ownership-state';
import CharacterQuestReadinessDefinition from '../../definitions/character-quest-readiness-definition';

export default interface UseCharacterQuestDetailDefinition {
  quest: QuestDetailDefinition | null;
  completedQuestIds: number[];
  readiness: CharacterQuestReadinessDefinition | null;
  questItemOwnership: Record<number, QuestItemOwnershipState>;
  loading: boolean;
  error: AxiosErrorDefinition | null;
  refresh: () => void;
}
