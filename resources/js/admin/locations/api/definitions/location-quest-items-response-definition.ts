import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';

import AdminQuestItemPresentationDefinition from '../../../items/api/definitions/admin-quest-item-presentation-definition';

export interface LocationDropModeDefinition {
  manual_fighting_only: boolean;
  is_cave_of_memories: boolean;
}

export interface LocationQuestItemsResponseDefinition extends PaginatedApiResponseDefinition<
  AdminQuestItemPresentationDefinition[]
> {
  meta: PaginatedApiResponseDefinition<
    AdminQuestItemPresentationDefinition[]
  >['meta'] & {
    location_drop_mode: LocationDropModeDefinition;
  };
}
