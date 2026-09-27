import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';

import SkillListDefinition from './skill-list-definition';

export type SkillListResponseDefinition = PaginatedApiResponseDefinition<
  SkillListDefinition[]
>;
