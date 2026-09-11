import PaginatedApiHandlerDefinition from 'api-handler/definitions/paginated-api-handler-definition';
import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';

import GemProfileParticipationRowDefinition from '../definitions/gem-profile-participation-row-definition';
import { GemProgressionApiUrls } from '../enums/gem-progression-api-urls';

export const useAllGemProfileParticipation = (
  characterId: number
): PaginatedApiHandlerDefinition<GemProfileParticipationRowDefinition, never> =>
  UsePaginatedApiHandler<GemProfileParticipationRowDefinition, never>(
    {
      url: GemProgressionApiUrls.ALL_PROFILE_PARTICIPATION,
      urlParams: { character: characterId },
      enabled: characterId > 0,
    },
    10
  );
