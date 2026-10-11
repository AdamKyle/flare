import { Screens } from 'configuration/screen-manager/screen-manager-constants';
import {
  useBindScreen,
  useScreenNavigation,
} from 'configuration/screen-manager/screen-manager-kit';
import { ScreenPropsOf } from 'configuration/screen-manager/screen-manager-props';
import { useEffect, useRef } from 'react';

import { useGuideQuestData } from '../../components/guide-quests/hooks/use-guide-quest-data';
import { useManageGuideQuestsVisibility } from '../../components/guide-quests/hooks/use-manage-guide-quests-visibility';
import {
  hasSeenGuideQuest,
  markGuideQuestSeen,
} from '../../components/guide-quests/utils/guide-quest-seen-storage';

import { useGameData } from 'game-data/hooks/use-game-data';

const BindGuideQuestsSection = () => {
  const { pop } = useScreenNavigation();
  const { closeGuideQuestsScreen, openGuideQuestsScreen, showGuideQuests } =
    useManageGuideQuestsVisibility();

  const activeRef = useRef(false);
  const autoOpenedRef = useRef(false);
  const { gameData, setGuideQuestAttention } = useGameData();
  const characterId = gameData?.character?.id ?? 0;
  const { data, loading } = useGuideQuestData();
  const hasGuideQuestReadyToHandIn =
    data?.can_hand_in.some((quest) => quest.can_hand_in) ?? false;

  useEffect(() => {
    setGuideQuestAttention(hasGuideQuestReadyToHandIn);
  }, [hasGuideQuestReadyToHandIn, setGuideQuestAttention]);

  useEffect(() => {
    if (!showGuideQuests || loading || characterId <= 0 || !data) {
      return;
    }

    data.quests
      .filter((quest) => !hasSeenGuideQuest(characterId, quest.id))
      .forEach((quest) => markGuideQuestSeen(characterId, quest.id));
  }, [characterId, data, loading, showGuideQuests]);

  useEffect(() => {
    if (autoOpenedRef.current || loading || characterId <= 0 || !data) {
      return;
    }

    const hasUnseenQuest = data.quests.some(
      (quest) => !hasSeenGuideQuest(characterId, quest.id)
    );

    if (!hasUnseenQuest) {
      return;
    }

    autoOpenedRef.current = true;
    openGuideQuestsScreen();
  }, [characterId, data, loading, openGuideQuestsScreen]);

  useBindScreen({
    when: showGuideQuests,
    to: Screens.GUIDE_QUESTS,
    props: (): ScreenPropsOf<typeof Screens.GUIDE_QUESTS> => ({
      on_close: () => {
        if (activeRef.current) {
          pop();
        }
        closeGuideQuestsScreen();
        activeRef.current = false;
      },
    }),
    mode: 'push',
    dedupeKey: 'guide-quests',
  });

  return null;
};

export default BindGuideQuestsSection;
