import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';

import ClassListDefinition from './class-list-definition';

export type ClassListResponseDefinition = PaginatedApiResponseDefinition<
  ClassListDefinition[]
>;
