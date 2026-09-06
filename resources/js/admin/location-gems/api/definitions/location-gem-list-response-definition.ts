import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';

import LocationGemListDefinition from './location-gem-list-definition';

export type LocationGemListResponseDefinition = PaginatedApiResponseDefinition<
  LocationGemListDefinition[]
>;
