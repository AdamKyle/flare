import { CharacterQuestWebsocketChannels } from '../enums/character-quest-websocket-channels';
import { CharacterQuestWebsocketEventNames } from '../enums/character-quest-websocket-event-names';
import UseCharacterQuestWebsocketParams from './definitions/use-character-quest-websocket-params';

import { ChannelType } from 'websockets/enums/channel-type';
import { useWebsocket } from 'websockets/hooks/use-websocket';

export const useCharacterQuestWebsocket = ({
  onQuestsUpdated,
  enabled = true,
}: UseCharacterQuestWebsocketParams) => {
  useWebsocket({
    url: CharacterQuestWebsocketChannels.UPDATE_QUESTS,
    params: {},
    type: ChannelType.PRESENCE,
    channelName: CharacterQuestWebsocketEventNames.UPDATE_QUESTS,
    onEvent: onQuestsUpdated,
    enabled,
  });

  useWebsocket({
    url: CharacterQuestWebsocketChannels.UPDATE_RAID_QUESTS,
    params: {},
    type: ChannelType.PRESENCE,
    channelName: CharacterQuestWebsocketEventNames.UPDATE_RAID_QUESTS,
    onEvent: onQuestsUpdated,
    enabled,
  });
};
