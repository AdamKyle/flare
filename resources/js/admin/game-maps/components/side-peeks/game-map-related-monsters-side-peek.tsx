import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import GameMapRelatedMonstersSidePeekProps from './types/game-map-related-monsters-side-peek-props';
import { resolveSidePeekComponent } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-mapper';
import { SidePeekComponentRegistrationEnum } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import GameMapRelatedMonsterDefinition from '../../api/definitions/game-map-related-monster-definition';
import { useGameMapRelatedMonsters } from '../../api/hooks/use-game-map-related-monsters';

import StackedCard from 'ui/cards/stacked-card';
import InfiniteScroll from 'ui/infinite-scroll/infinite-scroll';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const resolveCategoryLabel = (
  monster: GameMapRelatedMonsterDefinition
): string => {
  if (monster.is_celestial_entity) {
    return 'Celestial';
  }

  if (monster.is_raid_boss) {
    return 'Raid Boss';
  }

  if (monster.is_raid_monster) {
    return 'Raid Monster';
  }

  if (monster.only_for_location_type !== null) {
    return 'Special Location';
  }

  return 'Regular';
};

/**
 * Bounded, append-paginated browser for the Monsters available on a Game
 * Map (direct Map Monsters plus applicable special-location Monsters),
 * opened from the Game Map's "Related Game Data" hub. Each result opens
 * the canonical Monster detail inside a `StackedCard` while preserving
 * this relationship browser underneath (mounted, with its scroll position
 * intact) rather than replacing it through the global SidePeek emitter.
 */
const GameMapRelatedMonstersSidePeek = ({
  game_map_id: gameMapId,
}: GameMapRelatedMonstersSidePeekProps): ReactNode => {
  const monsters = useGameMapRelatedMonsters(gameMapId);
  const [selectedMonsterId, setSelectedMonsterId] = useState<number | null>(
    null
  );

  const handleOpenMonster = (id: number): void => {
    setSelectedMonsterId(id);
  };

  const handleCloseMonster = (): void => {
    setSelectedMonsterId(null);
  };

  const handleScroll = (event: React.UIEvent<HTMLDivElement>): void => {
    const target = event.currentTarget;
    const nearBottom =
      target.scrollHeight - target.scrollTop - target.clientHeight < 100;

    if (nearBottom) {
      monsters.on_end_reached();
    }
  };

  const renderRow = (monster: GameMapRelatedMonsterDefinition): ReactNode => (
    <button
      key={monster.id}
      type="button"
      onClick={() => handleOpenMonster(monster.id)}
      className="border-glacier-200 dark:border-glacier-800 bg-glacier-50 dark:bg-glacier-900/40 hover:bg-glacier-100 dark:hover:bg-glacier-900 focus-visible:ring-danube-400 w-full rounded-md border px-3 py-2 text-left focus:outline-none focus-visible:ring-2"
    >
      <p className="text-glacier-900 dark:text-glacier-100 font-medium">
        {monster.name}
      </p>
      <p className="text-glacier-600 dark:text-glacier-400 text-xs">
        {resolveCategoryLabel(monster)}
      </p>
    </button>
  );

  const renderSelectedMonster = (): ReactNode => {
    if (selectedMonsterId === null) {
      return null;
    }

    const AdminMonsterDetail = resolveSidePeekComponent(
      SidePeekComponentRegistrationEnum.ADMIN_MONSTER_DETAIL
    );

    return (
      <StackedCard on_close={handleCloseMonster} aria_label="Monster Details">
        <AdminMonsterDetail
          is_open
          title="Monster Details"
          monster_id={selectedMonsterId}
        />
      </StackedCard>
    );
  };

  const renderContent = (): ReactNode => {
    if (monsters.loading) {
      return <InfiniteLoader />;
    }

    if (monsters.error) {
      return (
        <div className="px-4">
          <ApiErrorAlert
            apiError={monsters.error.message ?? 'Unable to load Monsters.'}
          />
        </div>
      );
    }

    if (monsters.data.length === 0) {
      return (
        <p className="text-glacier-700 dark:text-glacier-300 px-4 text-sm">
          No Monsters are available on this Game Map.
        </p>
      );
    }

    return (
      <div className="h-[500px] max-h-[500px] px-4">
        <InfiniteScroll handle_scroll={handleScroll}>
          <div className="flex flex-col gap-2">
            {monsters.data.map(renderRow)}
            {monsters.is_loading_more && <InfiniteLoader />}
          </div>
        </InfiniteScroll>
      </div>
    );
  };

  return (
    <div className="space-y-4">
      {renderContent()}
      {renderSelectedMonster()}
    </div>
  );
};

export default GameMapRelatedMonstersSidePeek;
