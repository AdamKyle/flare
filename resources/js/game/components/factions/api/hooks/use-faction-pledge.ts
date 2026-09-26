import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { useCallback } from 'react';

import UseFactionPledgeDefinition from './definitions/use-faction-pledge-definition';
import { useFactionPostRequest } from './use-faction-post-request';
import PledgeResponseDefinition from '../definitions/pledge-response-definition';
import { FactionsApiUrls } from '../enums/factions-api-urls';

export const useFactionPledge = (
  characterId: number
): UseFactionPledgeDefinition => {
  const { getUrl } = useApiHandler();
  const { submitting, error, submit } = useFactionPostRequest();

  const pledge = useCallback(
    (factionId: number) =>
      submit<PledgeResponseDefinition, Record<string, never>>(
        getUrl(FactionsApiUrls.PLEDGE, {
          character: characterId,
          faction: factionId,
        }),
        {},
        'Unable to pledge to this Faction.'
      ),
    [submit, getUrl, characterId]
  );

  const removePledge = useCallback(
    (factionId: number) =>
      submit<PledgeResponseDefinition, Record<string, never>>(
        getUrl(FactionsApiUrls.REMOVE_PLEDGE, {
          character: characterId,
          faction: factionId,
        }),
        {},
        'Unable to remove your pledge from this Faction.'
      ),
    [submit, getUrl, characterId]
  );

  return { submitting, error, pledge, remove_pledge: removePledge };
};
