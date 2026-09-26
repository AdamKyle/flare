import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { resolveApiErrorMessage } from 'api-handler/utils/resolve-api-error-message';
import axios from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import UseFactionLoyaltyStatusDefinition from './definitions/use-faction-loyalty-status-definition';
import UseFactionLoyaltyStatusParams from './definitions/use-faction-loyalty-status-params';
import FactionLoyaltyInfoDefinition from '../definitions/faction-loyalty-info-definition';
import FactionLoyaltyUpdateEventDefinition from '../definitions/faction-loyalty-update-event-definition';
import FactionLoyaltyWarningNoticeDefinition from '../definitions/faction-loyalty-warning-notice-definition';
import FactionLoyaltyWarningStateDefinition from '../definitions/faction-loyalty-warning-state-definition';
import { FactionLoyaltyWebSocketChannels } from '../enums/faction-loyalty-web-socket-channels';
import { FactionLoyaltyWebSocketEventNames } from '../enums/faction-loyalty-web-socket-event-names';
import { FactionsApiUrls } from '../enums/factions-api-urls';

import { ChannelType } from 'websockets/enums/channel-type';
import { useWebsocket } from 'websockets/hooks/use-websocket';

export const useFactionLoyaltyStatus = ({
  character_id: characterId,
  user_id: userId,
  is_pledged: isPledged,
  initial_warning_notices: initialWarningNotices,
}: UseFactionLoyaltyStatusParams): UseFactionLoyaltyStatusDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [info, setInfo] = useState<FactionLoyaltyInfoDefinition | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [liveWarningNotices, setLiveWarningNotices] = useState<
    FactionLoyaltyWarningNoticeDefinition[] | null
  >(null);
  const [liveUpdateCount, setLiveUpdateCount] = useState(0);

  const abortControllerRef = useRef<AbortController | null>(null);

  const fetchInfo = useCallback(async (): Promise<void> => {
    if (characterId <= 0 || !isPledged) {
      setInfo(null);

      return;
    }

    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    try {
      const result = await apiHandler.get<FactionLoyaltyInfoDefinition, never>(
        getUrl(FactionsApiUrls.FACTION_LOYALTY, { character: characterId }),
        { signal: controller.signal }
      );

      if (abortControllerRef.current !== controller) {
        return;
      }

      setInfo(result);
    } catch (requestError) {
      if (axios.isCancel(requestError)) {
        return;
      }

      if (abortControllerRef.current !== controller) {
        return;
      }

      setError(
        resolveApiErrorMessage(
          requestError,
          'Unable to load your Faction Loyalty.'
        )
      );
    } finally {
      if (abortControllerRef.current === controller) {
        abortControllerRef.current = null;
        setLoading(false);
      }
    }
  }, [apiHandler, getUrl, characterId, isPledged]);

  useEffect(() => {
    void fetchInfo();

    return () => {
      abortControllerRef.current?.abort();
    };
  }, [fetchInfo]);

  const handleFactionLoyaltyUpdate = useCallback(
    (event: FactionLoyaltyUpdateEventDefinition) => {
      setInfo(event.factionLoyalty);
      setError(null);
      setLiveUpdateCount((currentCount) => currentCount + 1);
    },
    []
  );

  const applyWarningState = useCallback(
    (warningState: FactionLoyaltyWarningStateDefinition) => {
      setLiveWarningNotices(warningState.warning_notices);
    },
    []
  );

  const handleWarningState = useCallback(
    (warningState: FactionLoyaltyWarningStateDefinition) => {
      applyWarningState(warningState);
      setLiveUpdateCount((currentCount) => currentCount + 1);
    },
    [applyWarningState]
  );

  useWebsocket<FactionLoyaltyUpdateEventDefinition>({
    url: FactionLoyaltyWebSocketChannels.UPDATE,
    params: { userId },
    type: ChannelType.PRIVATE,
    channelName: FactionLoyaltyWebSocketEventNames.UPDATE,
    onEvent: handleFactionLoyaltyUpdate,
    enabled: userId > 0,
  });

  useWebsocket<FactionLoyaltyWarningStateDefinition>({
    url: FactionLoyaltyWebSocketChannels.AUTOMATION_WARNING,
    params: { userId },
    type: ChannelType.PRIVATE,
    channelName: FactionLoyaltyWebSocketEventNames.AUTOMATION_WARNING,
    onEvent: handleWarningState,
    enabled: userId > 0,
  });

  const refetch = useCallback(() => {
    void fetchInfo();
  }, [fetchInfo]);

  return {
    info,
    loading,
    error,
    warning_notices: liveWarningNotices ?? initialWarningNotices,
    live_update_count: liveUpdateCount,
    refetch,
    apply_warning_state: applyWarningState,
  };
};
