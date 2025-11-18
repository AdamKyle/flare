import { useEffect, useMemo, useState } from 'react';

import { UseChatStreamDefinition } from './definitions/use-chat-stream-definition';
import UseChatStreamParams from './definitions/use-chat-stream-params';
import { useAnnouncementMessages } from './use-announcement-messages';
import { useChatMessages } from './use-chat-messages';
import { useExplorationMessages } from './use-exploration-messages';
import { useServerMessages } from './use-server-messages';

export const useChatStream = ({
  character_data,
}: UseChatStreamParams): UseChatStreamDefinition => {
  const userId = character_data?.user_id ?? 0;

  const { serverMessages } = useServerMessages({ user_id: userId });
  const { explorationMessages } = useExplorationMessages({ user_id: userId });
  const { announcementMessages } = useAnnouncementMessages();
  const { chatMessages } = useChatMessages();

  const [ready, setReady] = useState(false);

  useEffect(() => {
    setReady(true);
  }, []);

  return useMemo(
    () => ({
      server: serverMessages,
      exploration: explorationMessages,
      announcements: announcementMessages,
      chatMessages,
      ready,
    }),
    [
      serverMessages,
      explorationMessages,
      announcementMessages,
      chatMessages,
      ready,
    ]
  );
};
