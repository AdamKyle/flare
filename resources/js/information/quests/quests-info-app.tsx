import ApiErrorAlert from 'api-handler/components/api-error-alert';
import { ApiHandlerProvider } from 'api-handler/components/api-handler-provider';
import React, { ReactNode } from 'react';
import { createRoot } from 'react-dom/client';

import { usePublicQuestDetail } from './api/hooks/use-public-quest-detail';
import QuestInfoTreePage from './components/quest-info-tree-page';
import PublicQuestDetailPageProps from './components/types/public-quest-detail-page-props';
import QuestDetail from '../../game/reusable-components/quest/components/quest-detail';

import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const navigateTo =
  (path: string) =>
  (id: number): void => {
    window.location.href = `${path}/${id}`;
  };

const PublicQuestDetailPage = ({
  questId,
}: PublicQuestDetailPageProps): ReactNode => {
  const { quest, loading, error } = usePublicQuestDetail(questId);

  if (loading) {
    return <InfiniteLoader />;
  }

  if (error || !quest) {
    return (
      <ApiErrorAlert
        apiError={error?.message ?? 'Unable to load this Quest.'}
      />
    );
  }

  return (
    <QuestDetail
      quest={quest}
      navigation={{
        on_open_quest: navigateTo('/information/quests'),
        on_open_npc: navigateTo('/information/npcs'),
        on_open_map: navigateTo('/information/map'),
        on_open_item: navigateTo('/information/item'),
        on_open_raid: navigateTo('/information/raids'),
        on_open_passive: navigateTo('/information/passive-skill'),
      }}
    />
  );
};

const renderQuestInformationContent = (
  questIdAttribute: string | undefined
): ReactNode => {
  if (!questIdAttribute) {
    return <QuestInfoTreePage />;
  }

  return <PublicQuestDetailPage questId={Number(questIdAttribute)} />;
};

const mountElement = document.getElementById('quest-info-app');

if (mountElement) {
  const questIdAttribute = mountElement.dataset.questId;

  createRoot(mountElement).render(
    <ApiHandlerProvider>
      {renderQuestInformationContent(questIdAttribute)}
    </ApiHandlerProvider>
  );
}
