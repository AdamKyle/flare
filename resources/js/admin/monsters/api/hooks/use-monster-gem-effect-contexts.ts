import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';

import UseMonsterGemEffectContextsDefinition from './definitions/use-monster-gem-effect-contexts-definition';
import UseMonsterGemEffectContextsParams from './definitions/use-monster-gem-effect-contexts-params';
import { MonsterGemEffectContextDefinition } from '../../../../game/reusable-components/monster/api/definitions/monster-detail-definition';
import { MonsterApiUrls } from '../enums/monster-api-urls';

export const useMonsterGemEffectContexts = ({
  monster_id: monsterId,
  enabled,
}: UseMonsterGemEffectContextsParams): UseMonsterGemEffectContextsDefinition => {
  const { data, loading, isLoadingMore, error, canLoadMore, onEndReached } =
    UsePaginatedApiHandler<MonsterGemEffectContextDefinition>(
      {
        url: MonsterApiUrls.GEM_EFFECT_CONTEXTS,
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
