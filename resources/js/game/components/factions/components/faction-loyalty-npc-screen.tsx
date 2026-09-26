import React, { ReactNode, useState } from 'react';

import FactionLoyaltyNpcContent from './faction-loyalty-npc-content';
import FactionLoyaltyNpcScreenProps from './types/faction-loyalty-npc-screen-props';
import PlayerNpcDetailStack from '../../side-peeks/game-data/components/player-npc-detail-stack';
import AssistNpcResponseDefinition from '../api/definitions/assist-npc-response-definition';
import { useFactionLoyaltyNpcAssistance } from '../api/hooks/use-faction-loyalty-npc-assistance';
import { useFactionLoyaltyContext } from '../hooks/use-faction-loyalty-context';

import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const FactionLoyaltyNpcScreen = ({
  character_id: characterId,
  faction_loyalty_npc_id: factionLoyaltyNpcId,
}: FactionLoyaltyNpcScreenProps): ReactNode => {
  const { info, loading, refetch } = useFactionLoyaltyContext();
  const {
    submitting,
    error,
    assist,
    stop_assisting: stopAssisting,
  } = useFactionLoyaltyNpcAssistance(characterId);

  const [assistanceMessage, setAssistanceMessage] = useState<string | null>(
    null
  );
  const [isViewingNpcDetail, setIsViewingNpcDetail] = useState(false);

  const factionLoyaltyNpc =
    info?.faction_loyalty.faction_loyalty_npcs.find(
      (candidate) => candidate.id === factionLoyaltyNpcId
    ) ?? null;

  const applyAssistanceResponse = (
    response: AssistNpcResponseDefinition | null
  ) => {
    if (response === null) {
      return;
    }

    setAssistanceMessage(response.message);
    refetch();
  };

  const handleAssist = async () => {
    setAssistanceMessage(null);

    applyAssistanceResponse(await assist(factionLoyaltyNpcId));
  };

  const handleStopAssisting = async () => {
    setAssistanceMessage(null);

    applyAssistanceResponse(await stopAssisting(factionLoyaltyNpcId));
  };

  const renderNpcDetail = (): ReactNode => {
    if (!isViewingNpcDetail || factionLoyaltyNpc === null) {
      return null;
    }

    return (
      <PlayerNpcDetailStack
        npc_id={factionLoyaltyNpc.npc_id}
        on_close={() => setIsViewingNpcDetail(false)}
      />
    );
  };

  const renderContent = (): ReactNode => {
    if (factionLoyaltyNpc === null && (loading || info === null)) {
      return <InfiniteLoader />;
    }

    if (factionLoyaltyNpc === null) {
      return (
        <p className="text-sm text-gray-700 dark:text-gray-300">
          This NPC is no longer available for this Faction.
        </p>
      );
    }

    return (
      <FactionLoyaltyNpcContent
        character_id={characterId}
        faction_loyalty_npc={factionLoyaltyNpc}
        submitting={submitting}
        error={error}
        assistance_message={assistanceMessage}
        on_assist={() => void handleAssist()}
        on_stop_assisting={() => void handleStopAssisting()}
        on_view_npc_details={() => setIsViewingNpcDetail(true)}
      />
    );
  };

  return (
    <>
      <div className="flex min-h-0 flex-1 flex-col overflow-y-auto">
        {renderContent()}
      </div>
      {renderNpcDetail()}
    </>
  );
};

export default FactionLoyaltyNpcScreen;
