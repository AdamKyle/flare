import UseListenForMapNameChangeParams from './definitions/use-listen-for-map-name-change-params';
import UseListenForMapNameChangeStreamResponse from './definitions/use-listen-for-map-name-change-stream-response';
import { ChannelType } from '../../../../../../../../websocket-handler/enums/channel-type';
import { useWebsocket } from '../../../../../../../../websocket-handler/hooks/use-websocket';
import { MapChannels } from '../enums/map-channels';
import { MapWebsocketEventNames } from '../enums/map-websocket-event-names';

export const useListenForMapNameChange = ({
  character_data,
  updateCharacterData,
}: UseListenForMapNameChangeParams) => {
  const handleEventData = (data: UseListenForMapNameChangeStreamResponse) => {
    const characterData = { ...character_data, ...data.characterMapName };

    updateCharacterData(characterData);
  };

  useWebsocket({
    url: MapChannels.CHARACTER_MAP_NAME_UPDATE,
    params: {
      userId: character_data?.user_id || 0,
    },
    type: ChannelType.PRIVATE,
    channelName: MapWebsocketEventNames.CHARACTER_MAP_NAME_UPDATE,
    onEvent: handleEventData,
  });
};
