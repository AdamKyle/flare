import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';

import ClassMasteryListDefinition from './class-mastery-list-definition';

export type ClassMasteryListResponseDefinition = PaginatedApiResponseDefinition<
  ClassMasteryListDefinition[]
>;
