import React, { ReactNode, useMemo, useState } from 'react';

import GlobalProgressInfoScreen from './global-progress-info-screen';
import PersonalProgressInfoScreen from './personal-progress-info-screen';
import GemProgressionPanelProps from './types/gem-progression-panel-props';
import GemProgressionStatusDefinition from '../api/definitions/gem-progression-status-definition';
import { useFetchGemProgressionStatus } from '../api/hooks/use-fetch-gem-progression-status';
import { useGemProfileProgressionUpdate } from '../api/hooks/use-gem-profile-progression-update';
import { useOpenManageGemScrolls } from '../hooks/use-open-manage-gem-scrolls';

import { useGameData } from 'game-data/hooks/use-game-data';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import ProgressBar from 'ui/progress/progress-bar';
import Separator from 'ui/separator/separator';

type ActiveDetail = 'global' | 'personal' | null;

const GemProgressionPanel = ({
  character_id: characterId,
}: GemProgressionPanelProps): ReactNode => {
  const { data, loading, error } = useFetchGemProgressionStatus(characterId);
  const { gameData } = useGameData();
  const [activeDetail, setActiveDetail] = useState<ActiveDetail>(null);
  const { openManageGemScrolls } = useOpenManageGemScrolls();

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

  const { global, personal, active_scrolls: activeScrolls } = mergedData;
  const globalAtCap = global.level >= global.max_level;
  const personalAtCap = personal.level >= personal.max_level;
  const isDetailActive = activeDetail !== null;

  return (
    <>
      <div inert={isDetailActive} aria-hidden={isDetailActive}>
        <div className="flex h-full min-h-0 flex-col gap-4 overflow-y-auto px-4 py-4 sm:px-5">
          <div className="text-sm text-gray-700 dark:text-gray-300">
            {mergedData.profile.generated_game_map_name}
          </div>

          <div>
            <h3 className="mb-1 text-sm font-semibold text-gray-800 dark:text-gray-200">
              Global Progress
            </h3>
            <ProgressBar
              label="Global Level"
              value={global.xp}
              max={Math.max(global.next_level_xp, 1)}
              variant={ProgressBarVariant.PRIMARY}
              value_label={
                globalAtCap
                  ? `Level ${global.level} (Max)`
                  : `Level ${global.level} — ${global.xp}/${global.next_level_xp} XP`
              }
            />
          </div>

          <div>
            <h3 className="mb-1 text-sm font-semibold text-gray-800 dark:text-gray-200">
              Personal Progress
            </h3>
            <ProgressBar
              label="Personal Level"
              value={personal.xp}
              max={Math.max(personal.next_level_xp, 1)}
              variant={ProgressBarVariant.PRIMARY}
              value_label={
                personalAtCap
                  ? `Level ${personal.level} (Max)`
                  : `Level ${personal.level} — ${personal.xp}/${personal.next_level_xp} XP`
              }
            />
          </div>

          <Separator />

          <div className="flex flex-col gap-2 sm:flex-row">
            <Button
              label="Global Progress Info"
              variant={ButtonVariant.PRIMARY}
              additional_css="w-full sm:flex-1"
              on_click={() => setActiveDetail('global')}
            />
            <Button
              label="Personal Progress Info"
              variant={ButtonVariant.PRIMARY}
              additional_css="w-full sm:flex-1"
              on_click={() => setActiveDetail('personal')}
            />
          </div>

          <Separator />

          <div className="text-sm text-gray-700 dark:text-gray-300">
            {activeScrolls.count} active Gem Scroll
            {activeScrolls.count === 1 ? '' : 's'} — Total Bonus{' '}
            {(activeScrolls.total_primary_bonus * 100).toFixed(2)}% /{' '}
            {(activeScrolls.cap * 100).toFixed(0)}%
          </div>

          <Button
            label="Manage Gem Scrolls"
            variant={ButtonVariant.PRIMARY}
            on_click={() => openManageGemScrolls(characterId)}
          />
        </div>
      </div>

      {activeDetail === 'global' && (
        <GlobalProgressInfoScreen
          global={mergedData.global}
          reward_effect_breakdown={mergedData.reward_effect_breakdown}
          rarity_effect_breakdown={mergedData.rarity_effect_breakdown}
          on_close={() => setActiveDetail(null)}
        />
      )}

      {activeDetail === 'personal' && (
        <PersonalProgressInfoScreen
          personal={mergedData.personal}
          scroll_drop={mergedData.scroll_drop}
          reward_effect_breakdown={mergedData.reward_effect_breakdown}
          rarity_effect_breakdown={mergedData.rarity_effect_breakdown}
          on_close={() => setActiveDetail(null)}
        />
      )}
    </>
  );
};

export default GemProgressionPanel;
