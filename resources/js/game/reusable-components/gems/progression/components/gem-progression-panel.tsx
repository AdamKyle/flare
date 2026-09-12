import React, { ReactNode, useMemo, useState } from 'react';

import ActivateGemScrollSection from './activate-gem-scroll-section';
import CurrentProfileActiveScrollsList from './current-profile-active-scrolls-list';
import GemProgressionLevelSection from './gem-progression-level-section';
import GemProgressionPanelProps from './types/gem-progression-panel-props';
import GemProgressionStatusDefinition from '../api/definitions/gem-progression-status-definition';
import { useFetchGemProgressionStatus } from '../api/hooks/use-fetch-gem-progression-status';
import { useGemProfileProgressionUpdate } from '../api/hooks/use-gem-profile-progression-update';

import { useGameData } from 'game-data/hooks/use-game-data';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import Separator from 'ui/separator/separator';

const GemProgressionPanel = ({
  character_id: characterId,
}: GemProgressionPanelProps): ReactNode => {
  const { data, loading, error } = useFetchGemProgressionStatus(characterId);
  const { gameData } = useGameData();
  const [scrollListRefreshToken, setScrollListRefreshToken] = useState(0);

  const liveUpdate = gameData?.character?.gem_progression ?? null;

  const sharedGlobalUpdate = useGemProfileProgressionUpdate(
    data && data.profile !== null ? data.profile.type : null,
    data && data.profile !== null ? data.profile.id : null
  );

  const mergedData: GemProgressionStatusDefinition | null = useMemo(() => {
    if (!data || data.profile === null) {
      return data;
    }

    let merged = data;

    if (
      liveUpdate &&
      liveUpdate.profile.type === data.profile.type &&
      liveUpdate.profile.id === data.profile.id
    ) {
      merged = {
        ...merged,
        global: liveUpdate.global,
        personal: liveUpdate.personal,
        scroll_drop: liveUpdate.scroll_drop,
        active_scrolls: liveUpdate.active_scrolls,
      };
    }

    if (sharedGlobalUpdate) {
      merged = { ...merged, global: sharedGlobalUpdate };
    }

    return merged;
  }, [data, liveUpdate, sharedGlobalUpdate]);

  const handleScrollActivated = (): void => {
    setScrollListRefreshToken((previous) => previous + 1);
  };

  if (loading && !mergedData) {
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

  if (!mergedData || mergedData.profile === null) {
    return (
      <div className="p-4 text-sm text-gray-600 dark:text-gray-400">
        You are not currently inside a generated Gem World.
      </div>
    );
  }

  return (
    <div className="flex h-full min-h-0 flex-col gap-4 overflow-y-auto px-4 py-4 sm:px-5">
      <div className="text-sm text-gray-700 dark:text-gray-300">
        {mergedData.profile.generated_game_map_name}
      </div>

      <GemProgressionLevelSection
        global={mergedData.global}
        personal={mergedData.personal}
        scroll_drop={mergedData.scroll_drop}
        reward_effect_breakdown={mergedData.reward_effect_breakdown}
        rarity_effect_breakdown={mergedData.rarity_effect_breakdown}
      />

      <Separator />

      <ActivateGemScrollSection
        character_id={characterId}
        on_activated={handleScrollActivated}
      />

      <Separator />

      <div>
        <h4 className="text-glacier-800 dark:text-glacier-200 mb-2 text-xs font-semibold tracking-wide uppercase">
          Active Gem Scrolls ({mergedData.active_scrolls.count}) — Total Bonus{' '}
          {(mergedData.active_scrolls.total_primary_bonus * 100).toFixed(2)}% /{' '}
          {(mergedData.active_scrolls.cap * 100).toFixed(0)}%
        </h4>
        <CurrentProfileActiveScrollsList
          character_id={characterId}
          refresh_token={scrollListRefreshToken}
        />
      </div>
    </div>
  );
};

export default GemProgressionPanel;
