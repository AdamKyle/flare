import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import QuestBrowseOptionsDefinition from '../../../../../../reusable-components/quest/api/definitions/quest-browse-options-definition';

export default interface UseCharacterQuestBrowseOptionsDefinition {
  options: QuestBrowseOptionsDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
  refresh: () => void;
}
