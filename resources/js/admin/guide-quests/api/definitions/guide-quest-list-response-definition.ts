import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';

import GuideQuestListDefinition from './guide-quest-list-definition';

export type GuideQuestListResponseDefinition = PaginatedApiResponseDefinition<
  GuideQuestListDefinition[]
>;
