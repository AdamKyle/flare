import React, { ReactNode, useState } from 'react';

import FactionDetailScreenProps from './types/faction-detail-screen-props';
import PledgeResponseDefinition from '../api/definitions/pledge-response-definition';
import { useFactionPledge } from '../api/hooks/use-faction-pledge';

import { useGameData } from 'game-data/hooks/use-game-data';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const FactionDetailScreen = ({
  character_id: characterId,
  faction,
  on_faction_updated: onFactionUpdated,
  on_open_faction_loyalty: onOpenFactionLoyalty,
}: FactionDetailScreenProps): ReactNode => {
  const { gameData, updateCharacter } = useGameData();
  const {
    submitting: pledging,
    error: pledgeError,
    pledge,
    remove_pledge: removePledge,
  } = useFactionPledge(characterId);

  const [pledgeMessage, setPledgeMessage] = useState<string | null>(null);

  const factionId = faction.id;
  const isPledged =
    (gameData?.character?.pledged_to_faction_id ?? null) === factionId;

  const applyPledgeResponse = (
    response: PledgeResponseDefinition,
    nextPledgedFactionId: number | null
  ) => {
    const updatedFaction = response.factions.find(
      (candidate) => candidate.id === factionId
    );

    if (updatedFaction) {
      onFactionUpdated(updatedFaction);
    }

    setPledgeMessage(response.message);
    updateCharacter({
      pledged_to_faction_id: nextPledgedFactionId,
      can_see_pledge_tab: nextPledgedFactionId !== null,
    });
  };

  const handlePledge = async () => {
    setPledgeMessage(null);

    const response = await pledge(factionId);

    if (response === null) {
      return;
    }

    applyPledgeResponse(response, factionId);
  };

  const handleRemovePledge = async () => {
    setPledgeMessage(null);

    const response = await removePledge(factionId);

    if (response === null) {
      return;
    }

    applyPledgeResponse(response, null);
  };

  const renderPledgeFeedback = (): ReactNode => {
    if (pledgeError !== null) {
      return <Alert variant={AlertVariant.DANGER}>{pledgeError}</Alert>;
    }

    if (pledgeMessage !== null) {
      return <Alert variant={AlertVariant.SUCCESS}>{pledgeMessage}</Alert>;
    }

    return null;
  };

  const renderPledgeAction = (canPledge: boolean): ReactNode => {
    if (isPledged) {
      return (
        <Button
          label="Remove Pledge"
          variant={ButtonVariant.DANGER}
          additional_css="w-full"
          disabled={pledging}
          on_click={() => void handleRemovePledge()}
        />
      );
    }

    if (!canPledge) {
      return (
        <p className="text-sm text-gray-700 dark:text-gray-300">
          Master this Faction to pledge your loyalty to it.
        </p>
      );
    }

    return (
      <Button
        label="Pledge Loyalty"
        variant={ButtonVariant.SUCCESS}
        additional_css="w-full"
        disabled={pledging}
        on_click={() => void handlePledge()}
      />
    );
  };

  const renderFactionLoyaltyAction = (): ReactNode => {
    if (!isPledged) {
      return null;
    }

    return (
      <Button
        label="Faction Loyalty"
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full"
        on_click={onOpenFactionLoyalty}
      />
    );
  };

  return (
    <div className="flex flex-col gap-4">
      <Dl>
        <Dt>Level</Dt>
        <Dd>{faction.current_level}</Dd>
        <Dt>Points</Dt>
        <Dd>
          {faction.current_points} / {faction.points_needed}
        </Dd>
        <Dt>Title</Dt>
        <Dd>{faction.title ?? 'None yet'}</Dd>
        <Dt>Mastered</Dt>
        <Dd>{faction.maxed ? 'Yes' : 'No'}</Dd>
        <Dt>Pledged</Dt>
        <Dd>{isPledged ? 'Yes' : 'No'}</Dd>
      </Dl>
      {renderPledgeFeedback()}
      {renderPledgeAction(faction.maxed)}
      {renderFactionLoyaltyAction()}
    </div>
  );
};

export default FactionDetailScreen;
