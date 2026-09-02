import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import GameMapRelatedMonstersSidePeekProps from './types/game-map-related-monsters-side-peek-props';
import { resolveSidePeekComponent } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-mapper';
import { SidePeekComponentRegistrationEnum } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import MonsterCard from '../../../../game/reusable-components/monster/components/monster-card';
import GameMapRelatedMonsterDefinition from '../../api/definitions/game-map-related-monster-definition';
import { useGameMapRelatedMonsters } from '../../api/hooks/use-game-map-related-monsters';

import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';
import InfiniteScroll from 'ui/infinite-scroll/infinite-scroll';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

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
    <MonsterCard
      key={monster.id}
      monster_id={monster.id}
      name={monster.name}
      is_celestial_entity={monster.is_celestial_entity}
      is_raid_boss={monster.is_raid_boss}
      is_raid_monster={monster.is_raid_monster}
      only_for_location_type={monster.only_for_location_type}
      on_open_monster={handleOpenMonster}
    />
  );

  const renderSelectedMonster = (): ReactNode => {
    if (selectedMonsterId === null) {
      return null;
    }

    const AdminMonsterDetail = resolveSidePeekComponent(
      SidePeekComponentRegistrationEnum.ADMIN_MONSTER_DETAIL
    );

    return (
      <StackedCard
        on_close={handleCloseMonster}
        aria_label="Monster Details"
        content_mode={StackedCardContentMode.FULL_BLEED}
      >
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
        <div className="px-4 py-3">
          <ApiErrorAlert
            apiError={monsters.error.message ?? 'Unable to load Monsters.'}
          />
        </div>
      );
    }

    if (monsters.data.length === 0) {
      return (
        <p className="text-glacier-700 dark:text-glacier-300 px-4 py-3 text-sm">
          No Monsters are available on this Game Map.
        </p>
      );
    }

    return (
      <div className="min-h-0 flex-1 px-2 py-2">
        <InfiniteScroll
          handle_scroll={handleScroll}
          height_class="h-full min-h-0"
        >
          <div className="flex flex-col gap-2">
            {monsters.data.map(renderRow)}
            {monsters.is_loading_more && <InfiniteLoader />}
          </div>
        </InfiniteScroll>
      </div>
    );
  };

  return (
    <div className="relative flex h-full min-h-0 flex-col overflow-hidden">
      {renderContent()}
      {renderSelectedMonster()}
    </div>
  );
};

export default GameMapRelatedMonstersSidePeek;
