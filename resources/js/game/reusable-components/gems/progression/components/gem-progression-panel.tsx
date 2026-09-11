import React, { ReactNode } from 'react';

import ActivateGemScrollSection from './activate-gem-scroll-section';
import ActiveGemScrollsSection from './active-gem-scrolls-section';
import GemProgressionLevelSection from './gem-progression-level-section';
import GemWorldIntroduction from './gem-world-introduction';
import GemProgressionPanelProps from './types/gem-progression-panel-props';
import { useAcknowledgeGemWorldIntroduction } from '../api/hooks/use-acknowledge-gem-world-introduction';
import { useFetchGemProgressionStatus } from '../api/hooks/use-fetch-gem-progression-status';

import { useGameData } from 'game-data/hooks/use-game-data';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import Separator from 'ui/separator/separator';

const GemProgressionPanel = ({
  character_id: characterId,
}: GemProgressionPanelProps): ReactNode => {
  const { gameData } = useGameData();
  const introductionAcknowledgedAt =
    gameData?.character?.gem_world_introduction_acknowledged_at ?? null;

  const { data, loading, error, refetch } =
    useFetchGemProgressionStatus(characterId);

  const {
    loading: acknowledging,
    error: acknowledgeError,
    acknowledge,
  } = useAcknowledgeGemWorldIntroduction(characterId);

  const handleAcknowledge = (): void => {
    void acknowledge();
  };

  if (!introductionAcknowledgedAt) {
    return (
      <div className="flex h-full min-h-0 flex-col gap-4 overflow-y-auto px-4 py-4 sm:px-5">
        <GemWorldIntroduction
          loading={acknowledging}
          error={acknowledgeError}
          on_acknowledge={handleAcknowledge}
        />
      </div>
    );
  }

  if (loading && !data) {
    return (
      <div className="p-4">
        <InfiniteLoader />
      </div>
    );
  }

  if (error) {
    return (
      <div className="p-4">
        <Alert variant={AlertVariant.DANGER}>{error}</Alert>
      </div>
    );
  }

  if (!data || data.profile === null) {
    return (
      <div className="p-4 text-sm text-gray-600 dark:text-gray-400">
        You are not currently inside a generated Gem World.
      </div>
    );
  }

  return (
    <div className="flex h-full min-h-0 flex-col gap-4 overflow-y-auto px-4 py-4 sm:px-5">
      <div className="text-sm text-gray-700 dark:text-gray-300">
        {data.profile.generated_game_map_name}
      </div>

      <GemProgressionLevelSection
        global={data.global}
        personal={data.personal}
        scroll_drop={data.scroll_drop}
      />

      <Separator />

      <ActivateGemScrollSection
        character_id={characterId}
        on_activated={refetch}
      />

      <Separator />

      <div>
        <h4 className="text-glacier-800 dark:text-glacier-200 mb-2 text-xs font-semibold tracking-wide uppercase">
          Active Gem Scrolls ({data.active_scrolls.count}) — Total Bonus{' '}
          {(data.active_scrolls.total_primary_bonus * 100).toFixed(2)}% /{' '}
          {(data.active_scrolls.cap * 100).toFixed(0)}%
        </h4>
        <ActiveGemScrollsSection
          character_id={characterId}
          scrolls={data.active_scroll_rows}
          on_changed={refetch}
        />
      </div>
    </div>
  );
};

export default GemProgressionPanel;
