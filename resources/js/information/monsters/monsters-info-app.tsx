import ApiErrorAlert from 'api-handler/components/api-error-alert';
import { ApiHandlerProvider } from 'api-handler/components/api-handler-provider';
import React, { ReactNode } from 'react';
import { createRoot } from 'react-dom/client';

import { usePublicMonsterDetail } from './api/hooks/use-public-monster-detail';
import PublicMonsterDetailPageProps from './components/types/public-monster-detail-page-props';
import MonsterDetail from '../../game/reusable-components/monster/components/monster-detail';

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

  return (
    <MonsterDetail
      monster={monster}
      navigation={{
        on_open_map: navigateTo('/information/map'),
        on_open_item: navigateTo('/information/item'),
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
