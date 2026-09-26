import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { useCallback } from 'react';

import UseFactionLoyaltyNpcAssistanceDefinition from './definitions/use-faction-loyalty-npc-assistance-definition';
import { useFactionPostRequest } from './use-faction-post-request';
import AssistNpcResponseDefinition from '../definitions/assist-npc-response-definition';
import { FactionsApiUrls } from '../enums/factions-api-urls';

export const useFactionLoyaltyNpcAssistance = (
  characterId: number
): UseFactionLoyaltyNpcAssistanceDefinition => {
  const { getUrl } = useApiHandler();
  const { submitting, error, submit } = useFactionPostRequest();

  const assist = useCallback(
    (factionLoyaltyNpcId: number) =>
      submit<AssistNpcResponseDefinition, Record<string, never>>(
        getUrl(FactionsApiUrls.ASSIST_NPC, {
          character: characterId,
          factionLoyaltyNpc: factionLoyaltyNpcId,
        }),
        {},
        'Unable to assist this NPC.'
      ),
    [submit, getUrl, characterId]
  );

  const stopAssisting = useCallback(
    (factionLoyaltyNpcId: number) =>
      submit<AssistNpcResponseDefinition, Record<string, never>>(
        getUrl(FactionsApiUrls.STOP_ASSISTING_NPC, {
          character: characterId,
          factionLoyaltyNpc: factionLoyaltyNpcId,
        }),
        {},
        'Unable to stop assisting this NPC.'
      ),
    [submit, getUrl, characterId]
  );

  return { submitting, error, assist, stop_assisting: stopAssisting };
};
