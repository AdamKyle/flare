import { useEffect, useState } from 'react';

import GemProfileProgressionUpdateDefinition from '../definitions/gem-profile-progression-update-definition';
import { GemProgressionGlobalDefinition } from '../definitions/gem-progression-status-definition';
import { GemProgressionWebSocketChannels } from '../enums/gem-progression-web-socket-channels';
import { GemProgressionWebSocketEventNames } from '../enums/gem-progression-web-socket-event-names';

import { ChannelType } from 'websockets/enums/channel-type';
import { useWebsocket } from 'websockets/hooks/use-websocket';

export const useGemProfileProgressionUpdate = (
  profileType: string | null,
  profileId: number | null
): GemProgressionGlobalDefinition | null => {
  const [globalUpdate, setGlobalUpdate] =
    useState<GemProgressionGlobalDefinition | null>(null);

  const enabled = profileType !== null && profileId !== null;

  useEffect(() => {
    setGlobalUpdate(null);
  }, [profileType, profileId]);

  useWebsocket<GemProfileProgressionUpdateDefinition>({
    url: GemProgressionWebSocketChannels.GEM_PROFILE_PROGRESSION,
    params: { profileType: profileType ?? '', profileId: profileId ?? 0 },
    type: ChannelType.PRIVATE,
    channelName: GemProgressionWebSocketEventNames.GEM_PROFILE_PROGRESSION,
    enabled,
    onEvent: (data) => setGlobalUpdate(data.globalProgress),
  });

  return globalUpdate;
};
