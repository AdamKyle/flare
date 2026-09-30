import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';

import { MonsterGemEffectContextDefinition } from '../definitions/monster-detail-definition';
import { MonsterDetailApiUrls } from '../enums/monster-detail-api-urls';
import UsePlayerMonsterGemEffectContextsDefinition from './definitions/use-player-monster-gem-effect-contexts-definition';
import UsePlayerMonsterGemEffectContextsParams from './definitions/use-player-monster-gem-effect-contexts-params';

export const usePlayerMonsterGemEffectContexts = ({
  monster_id: monsterId,
  enabled,
}: UsePlayerMonsterGemEffectContextsParams): UsePlayerMonsterGemEffectContextsDefinition => {
  const { data, loading, isLoadingMore, error, canLoadMore, onEndReached } =
    UsePaginatedApiHandler<MonsterGemEffectContextDefinition>(
      {
        url: MonsterDetailApiUrls.GEM_EFFECT_CONTEXTS,
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
