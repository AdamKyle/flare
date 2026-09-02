import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import QuestBrowseOptionsDefinition from '../../../../../game/reusable-components/quest/api/definitions/quest-browse-options-definition';

export default interface UseAdminGameMapFilterOptionsDefinition {
  options: QuestBrowseOptionsDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
