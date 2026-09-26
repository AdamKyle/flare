import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { useCallback } from 'react';

import UseFactionLoyaltyAutomationActionsDefinition from './definitions/use-faction-loyalty-automation-actions-definition';
import { useFactionPostRequest } from './use-faction-post-request';
import DismissAutomationWarningRequestDefinition from '../definitions/dismiss-automation-warning-request-definition';
import FactionLoyaltyWarningStateDefinition from '../definitions/faction-loyalty-warning-state-definition';
import FactionMessageResponseDefinition from '../definitions/faction-message-response-definition';
import StartFactionLoyaltyAutomationRequestDefinition from '../definitions/start-faction-loyalty-automation-request-definition';
import { FactionsApiUrls } from '../enums/factions-api-urls';

export const useFactionLoyaltyAutomationActions = (
  characterId: number
): UseFactionLoyaltyAutomationActionsDefinition => {
  const { getUrl } = useApiHandler();
  const { submitting, error, submit } = useFactionPostRequest();

  const start = useCallback(
    (attackType: string) =>
      submit<
        FactionMessageResponseDefinition,
        StartFactionLoyaltyAutomationRequestDefinition
      >(
        getUrl(FactionsApiUrls.START_AUTOMATION, { character: characterId }),
        { attack_type: attackType },
        'Unable to start Faction Loyalty automation.'
      ),
    [submit, getUrl, characterId]
  );

  const stop = useCallback(
    () =>
      submit<FactionMessageResponseDefinition, Record<string, never>>(
        getUrl(FactionsApiUrls.STOP_AUTOMATION, { character: characterId }),
        {},
        'Unable to stop Faction Loyalty automation.'
      ),
    [submit, getUrl, characterId]
  );

  const dismissWarning = useCallback(
    (warningId: number) =>
      submit<
        FactionLoyaltyWarningStateDefinition,
        DismissAutomationWarningRequestDefinition
      >(
        getUrl(FactionsApiUrls.DISMISS_AUTOMATION_WARNING, {
          character: characterId,
        }),
        { warning_id: warningId },
        'Unable to dismiss this Faction Loyalty warning.'
      ),
    [submit, getUrl, characterId]
  );

  return { submitting, error, start, stop, dismiss_warning: dismissWarning };
};
