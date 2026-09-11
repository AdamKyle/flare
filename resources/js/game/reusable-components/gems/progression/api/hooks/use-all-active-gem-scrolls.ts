import PaginatedApiHandlerDefinition from 'api-handler/definitions/paginated-api-handler-definition';
import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';

import ActiveGemScrollRowDefinition from '../definitions/active-gem-scroll-row-definition';
import { GemProgressionApiUrls } from '../enums/gem-progression-api-urls';

export const useAllActiveGemScrolls = (
  characterId: number
): PaginatedApiHandlerDefinition<ActiveGemScrollRowDefinition, never> =>
  UsePaginatedApiHandler<ActiveGemScrollRowDefinition, never>(
    {
      url: GemProgressionApiUrls.ALL_ACTIVE_SCROLLS,
      urlParams: { character: characterId },
      enabled: characterId > 0,
    },
    10
  );
