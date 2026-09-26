import React, { ReactNode } from 'react';

import FactionLoyaltyNpcCard from './faction-loyalty-npc-card';
import FactionLoyaltyWarnings from './faction-loyalty-warnings';
import FactionLoyaltyScreenProps from './types/faction-loyalty-screen-props';
import FactionLoyaltyNpcDefinition from '../api/definitions/faction-loyalty-npc-definition';
import { useFactionLoyaltyAutomationActions } from '../api/hooks/use-faction-loyalty-automation-actions';
import { FACTION_LIST_BATCH_SIZE } from '../constants/faction-list-constants';
import { useFactionLoyaltyContext } from '../hooks/use-faction-loyalty-context';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import { useProgressiveList } from 'ui/infinite-scroll/hooks/use-progressive-list';
import InfiniteScroll from 'ui/infinite-scroll/infinite-scroll';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const FactionLoyaltyScreen = ({
  character_id: characterId,
  on_open_npc: onOpenNpc,
}: FactionLoyaltyScreenProps): ReactNode => {
  const { info, loading, error, warning_notices, apply_warning_state } =
    useFactionLoyaltyContext();
  const { submitting: dismissingWarning, dismiss_warning: dismissWarning } =
    useFactionLoyaltyAutomationActions(characterId);

  const factionLoyaltyNpcs = info?.faction_loyalty.faction_loyalty_npcs ?? [];

  const { visible_count: visibleCount, handle_scroll: handleScroll } =
    useProgressiveList({
      total_items: factionLoyaltyNpcs.length,
      batch_size: FACTION_LIST_BATCH_SIZE,
      initial_count: FACTION_LIST_BATCH_SIZE,
    });

  const visibleNpcs = factionLoyaltyNpcs.slice(0, visibleCount);

  const handleDismissWarning = async (warningId: number) => {
    const warningState = await dismissWarning(warningId);

    if (warningState === null) {
      return;
    }

    apply_warning_state(warningState);
  };

  const renderNpcCard = (factionLoyaltyNpc: FactionLoyaltyNpcDefinition) => (
    <li key={factionLoyaltyNpc.id}>
      <FactionLoyaltyNpcCard
        faction_loyalty_npc={factionLoyaltyNpc}
        on_open={onOpenNpc}
      />
    </li>
  );

  const renderNpcs = (): ReactNode => {
    if (loading && info === null) {
      return <InfiniteLoader />;
    }

    if (error !== null) {
      return <Alert variant={AlertVariant.DANGER}>{error}</Alert>;
    }

    if (info === null) {
      return null;
    }

    return (
      <InfiniteScroll handle_scroll={handleScroll} additional_css="max-h-64">
        <ul className="flex flex-col gap-3">
          {visibleNpcs.map(renderNpcCard)}
        </ul>
        <p className="sr-only" role="status" aria-live="polite">
          Showing {visibleNpcs.length} of {factionLoyaltyNpcs.length} NPCs.
        </p>
      </InfiniteScroll>
    );
  };

  const renderInstructions = (): ReactNode => {
    if (info === null) {
      return null;
    }

    return (
      <p className="text-sm text-gray-700 dark:text-gray-300">
        Assist one NPC of {info.map_name} at a time with their bounty and
        crafting tasks to earn Fame.
      </p>
    );
  };

  return (
    <div className="flex flex-col gap-3">
      <FactionLoyaltyWarnings
        warning_notices={warning_notices}
        dismissing={dismissingWarning}
        on_dismiss={(warningId) => void handleDismissWarning(warningId)}
      />
      {renderInstructions()}
      {renderNpcs()}
    </div>
  );
};

export default FactionLoyaltyScreen;
