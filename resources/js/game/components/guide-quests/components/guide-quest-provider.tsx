import React, { useCallback } from 'react';

import GuideQuestProviderProps from './types/guide-quest-provider-props';
import CharacterSkillsUpdateEventDefinition from '../../character-sheet/skills/api/definitions/character-skills-update-event-definition';
import { SkillsWebSocketChannels } from '../../character-sheet/skills/api/enums/skills-web-socket-channels';
import { SkillsWebSocketEventNames } from '../../character-sheet/skills/api/enums/skills-web-socket-event-names';
import GuideQuestCompletedEventDefinition from '../api/definitions/guide-quest-completed-event-definition';
import { GuideQuestWebSocketChannels } from '../api/enums/guide-quest-web-socket-channels';
import { GuideQuestWebSocketEventNames } from '../api/enums/guide-quest-web-socket-event-names';
import { useGuideQuests } from '../api/hooks/use-guide-quests';
import { GuideQuestContext } from '../context/guide-quest-context';

import { ChannelType } from 'websockets/enums/channel-type';
import { useWebsocket } from 'websockets/hooks/use-websocket';

import { useGameData } from 'game-data/hooks/use-game-data';

const GuideQuestProvider = ({ children }: GuideQuestProviderProps) => {
  const { gameData, setGuideQuestAttention } = useGameData();
  const userId = gameData?.character?.user_id ?? 0;
  const guideQuestData = useGuideQuests(userId);
  const { data, fetchGuideQuests } = guideQuestData;
  const hasSkillRequirement =
    data?.quests.some(
      (quest) =>
        quest.required_skill !== null || quest.required_training_skill !== null
    ) ?? false;

  const handleSkillsUpdated = useCallback((): void => {
    void fetchGuideQuests();
  }, [fetchGuideQuests]);

  const handleGuideQuestCompleted = useCallback(
    (event: GuideQuestCompletedEventDefinition): void => {
      setGuideQuestAttention(event.showQuestCompleted);
      void fetchGuideQuests();
    },
    [fetchGuideQuests, setGuideQuestAttention]
  );

  useWebsocket<CharacterSkillsUpdateEventDefinition>({
    url: SkillsWebSocketChannels.UPDATE_SKILLS,
    params: { userId },
    type: ChannelType.PRIVATE,
    channelName: SkillsWebSocketEventNames.UPDATE_SKILLS,
    onEvent: handleSkillsUpdated,
    enabled: userId > 0 && hasSkillRequirement,
  });

  useWebsocket<GuideQuestCompletedEventDefinition>({
    url: GuideQuestWebSocketChannels.COMPLETED,
    params: { userId },
    type: ChannelType.PRIVATE,
    channelName: GuideQuestWebSocketEventNames.COMPLETED,
    onEvent: handleGuideQuestCompleted,
    enabled: userId > 0,
  });

  return (
    <GuideQuestContext.Provider value={guideQuestData}>
      {children}
    </GuideQuestContext.Provider>
  );
};

export default GuideQuestProvider;
