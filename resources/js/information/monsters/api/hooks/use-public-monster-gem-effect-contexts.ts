import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';

import { MonsterInfoApiUrls } from '../enums/monster-info-api-urls';
import UsePublicMonsterGemEffectContextsDefinition from './definitions/use-public-monster-gem-effect-contexts-definition';
import UsePublicMonsterGemEffectContextsParams from './definitions/use-public-monster-gem-effect-contexts-params';
import { MonsterGemEffectContextDefinition } from '../../../../game/reusable-components/monster/api/definitions/monster-detail-definition';

/**
 * Public, read-only paginated Monster Gem effect context hook. Calls the
 * public Information API, never an Admin endpoint.
 */
export const usePublicMonsterGemEffectContexts = ({
  monster_id: monsterId,
  enabled,
}: UsePublicMonsterGemEffectContextsParams): UsePublicMonsterGemEffectContextsDefinition => {
  const { data, loading, isLoadingMore, error, canLoadMore, onEndReached } =
    UsePaginatedApiHandler<MonsterGemEffectContextDefinition>(
      {
        url: MonsterInfoApiUrls.GEM_EFFECT_CONTEXTS,
        urlParams: { monster: monsterId },
        enabled,
      },
      10
    );

  return {
    context_rows: data,
    loading,
    loading_more: isLoadingMore,
    error: error?.message ?? null,
    has_more: canLoadMore,
    load_next: onEndReached,
  };
};
