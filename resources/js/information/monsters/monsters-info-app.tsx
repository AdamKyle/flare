import ApiErrorAlert from 'api-handler/components/api-error-alert';
import { ApiHandlerProvider } from 'api-handler/components/api-handler-provider';
import React, { ReactNode, useState } from 'react';
import { createRoot } from 'react-dom/client';

import { usePublicMonsterDetail } from './api/hooks/use-public-monster-detail';
import { usePublicMonsterGemEffectContexts } from './api/hooks/use-public-monster-gem-effect-contexts';
import PublicMonsterDetailPageProps from './components/types/public-monster-detail-page-props';
import { MonsterGemEffectContextDefinition } from '../../game/reusable-components/monster/api/definitions/monster-detail-definition';
import MonsterDetail from '../../game/reusable-components/monster/components/monster-detail';
import MonsterGemEffectContextCard from '../../game/reusable-components/monster/components/monster-gem-effect-context-card';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const navigateTo =
  (path: string) =>
  (id: number): void => {
    window.location.href = `${path}/${id}`;
  };

const PublicMonsterDetailPage = ({
  monsterId,
}: PublicMonsterDetailPageProps): ReactNode => {
  const { monster, loading, error } = usePublicMonsterDetail(monsterId);
  const [selectedContext, setSelectedContext] =
    useState<MonsterGemEffectContextDefinition | null>(null);

  const gemEffectContexts = usePublicMonsterGemEffectContexts({
    monster_id: monsterId,
    enabled: (monster?.gem_effect_context_count ?? 0) > 1,
  });

  if (loading) {
    return <InfiniteLoader />;
  }

  if (error || !monster) {
    return (
      <ApiErrorAlert
        apiError={error?.message ?? 'Unable to load this Monster.'}
      />
    );
  }

  if (selectedContext) {
    return (
      <div className="flex flex-col gap-4">
        <div>
          <Button
            label="Back to Monster"
            variant={ButtonVariant.PRIMARY}
            additional_css="text-sm px-3 py-1.5"
            on_click={() => setSelectedContext(null)}
          />
        </div>
        <MonsterGemEffectContextCard
          context={selectedContext}
          navigation={{
            on_open_map: navigateTo('/information/map'),
            on_open_item: navigateTo('/information/item'),
          }}
        />
      </div>
    );
  }

  return (
    <MonsterDetail
      monster={monster}
      presentation="page"
      navigation={{
        on_open_map: navigateTo('/information/map'),
        on_open_item: navigateTo('/information/item'),
      }}
      gem_effect_context_browser={{
        context_rows: gemEffectContexts.context_rows,
        loading: gemEffectContexts.loading,
        loading_more: gemEffectContexts.loading_more,
        error: gemEffectContexts.error,
        has_more: gemEffectContexts.has_more,
        on_load_next: gemEffectContexts.load_next,
        on_open_context: setSelectedContext,
      }}
    />
  );
};

const mountElement = document.getElementById('monster-info-app');

if (mountElement) {
  const monsterId = Number(mountElement.dataset.monsterId);

  createRoot(mountElement).render(
    <ApiHandlerProvider>
      <PublicMonsterDetailPage monsterId={monsterId} />
    </ApiHandlerProvider>
  );
}
