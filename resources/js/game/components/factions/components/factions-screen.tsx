import React, { ReactNode } from 'react';

import FactionCard from './faction-card';
import FactionsScreenProps from './types/factions-screen-props';
import FactionDefinition from '../api/definitions/faction-definition';
import { useFetchFactions } from '../api/hooks/use-fetch-factions';
import { FACTION_LIST_BATCH_SIZE } from '../constants/faction-list-constants';

import { useGameData } from 'game-data/hooks/use-game-data';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import { useProgressiveList } from 'ui/infinite-scroll/hooks/use-progressive-list';
import InfiniteScroll from 'ui/infinite-scroll/infinite-scroll';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const FactionsScreen = ({
  character_id: characterId,
  on_open_faction: onOpenFaction,
}: FactionsScreenProps): ReactNode => {
  const { gameData } = useGameData();
  const { factions, loading, error } = useFetchFactions(characterId);

  const { visible_count: visibleCount, handle_scroll: handleScroll } =
    useProgressiveList({
      total_items: factions.length,
      batch_size: FACTION_LIST_BATCH_SIZE,
      initial_count: FACTION_LIST_BATCH_SIZE,
    });

  const pledgedFactionId = gameData?.character?.pledged_to_faction_id ?? null;
  const visibleFactions = factions.slice(0, visibleCount);

  const handleOpenFaction = (factionId: number) => {
    const faction = factions.find((candidate) => candidate.id === factionId);

    if (!faction) {
      return;
    }

    onOpenFaction(faction);
  };

  const renderFactionCard = (faction: FactionDefinition) => (
    <li key={faction.id}>
      <FactionCard
        faction={faction}
        is_pledged={faction.id === pledgedFactionId}
        on_open={handleOpenFaction}
      />
    </li>
  );

  const renderFactions = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error !== null) {
      return <Alert variant={AlertVariant.DANGER}>{error}</Alert>;
    }

    if (factions.length === 0) {
      return (
        <p className="text-sm text-gray-700 dark:text-gray-300">
          You have no Factions yet.
        </p>
      );
    }

    return (
      <InfiniteScroll handle_scroll={handleScroll} additional_css="max-h-64">
        <ul className="flex flex-col gap-3">
          {visibleFactions.map(renderFactionCard)}
        </ul>
        <p className="sr-only" role="status" aria-live="polite">
          Showing {visibleFactions.length} of {factions.length} Factions.
        </p>
      </InfiniteScroll>
    );
  };

  return (
    <div className="flex flex-col gap-3">
      <p className="text-sm text-gray-700 dark:text-gray-300">
        Only one Faction can hold your pledge at a time.
      </p>
      {renderFactions()}
    </div>
  );
};

export default FactionsScreen;
