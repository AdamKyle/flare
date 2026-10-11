import React, { ReactNode, useEffect, useState } from 'react';

import FactionCard from './faction-card';
import FactionsScreenProps from './types/factions-screen-props';
import { GuideTargets } from '../../guide-quests/walkthrough/registry/guide-targets';
import FactionDefinition from '../api/definitions/faction-definition';
import { useFetchFactions } from '../api/hooks/use-fetch-factions';
import { FACTION_LIST_PAGE_SIZE } from '../constants/faction-list-constants';

import { useGameData } from 'game-data/hooks/use-game-data';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const FactionsScreen = ({
  character_id: characterId,
  on_open_faction: onOpenFaction,
}: FactionsScreenProps): ReactNode => {
  const { gameData } = useGameData();
  const { factions, loading, error } = useFetchFactions(characterId);

  const [currentPage, setCurrentPage] = useState(1);

  const totalPages = Math.max(
    1,
    Math.ceil(factions.length / FACTION_LIST_PAGE_SIZE)
  );

  useEffect(() => {
    if (currentPage <= totalPages) {
      return;
    }

    setCurrentPage(totalPages);
  }, [currentPage, totalPages]);

  const pageStart = (currentPage - 1) * FACTION_LIST_PAGE_SIZE;

  const visibleFactions = factions.slice(
    pageStart,
    pageStart + FACTION_LIST_PAGE_SIZE
  );

  const pledgedFactionId = gameData?.character?.pledged_to_faction_id ?? null;

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

  const renderPagination = (): ReactNode => {
    if (totalPages <= 1) {
      return null;
    }

    return (
      <div
        className="flex items-center justify-between gap-3"
        aria-label="Faction pagination"
      >
        <Button
          label="Previous"
          variant={ButtonVariant.PRIMARY}
          disabled={currentPage === 1}
          on_click={() => setCurrentPage((page) => Math.max(1, page - 1))}
        />
        <span className="text-sm text-gray-700 dark:text-gray-300">
          Page {currentPage} of {totalPages}
        </span>
        <Button
          label="Next"
          variant={ButtonVariant.PRIMARY}
          disabled={currentPage === totalPages}
          on_click={() =>
            setCurrentPage((page) => Math.min(totalPages, page + 1))
          }
        />
      </div>
    );
  };

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
      <div className="flex flex-col gap-3">
        <ul className="flex flex-col gap-3">
          {visibleFactions.map(renderFactionCard)}
        </ul>
        {renderPagination()}
        <p className="sr-only" role="status" aria-live="polite">
          Showing {pageStart + 1} to{' '}
          {Math.min(pageStart + visibleFactions.length, factions.length)} of{' '}
          {factions.length} Factions.
        </p>
      </div>
    );
  };

  return (
    <div
      className="flex flex-col gap-3"
      data-guide-target={GuideTargets.FACTIONS_OVERVIEW}
    >
      <p className="text-sm text-gray-700 dark:text-gray-300">
        Only one Faction can hold your pledge at a time.
      </p>
      {renderFactions()}
    </div>
  );
};

export default FactionsScreen;
