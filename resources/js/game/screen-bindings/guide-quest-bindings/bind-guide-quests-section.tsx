import { Screens } from 'configuration/screen-manager/screen-manager-constants';
import {
  useBindScreen,
  useScreenNavigation,
} from 'configuration/screen-manager/screen-manager-kit';
import { ScreenPropsOf } from 'configuration/screen-manager/screen-manager-props';
import { useEffect, useRef } from 'react';

import { useGuideQuestData } from '../../components/guide-quests/hooks/use-guide-quest-data';
import { useManageGuideQuestsVisibility } from '../../components/guide-quests/hooks/use-manage-guide-quests-visibility';

import { useGameData } from 'game-data/hooks/use-game-data';

const BindGuideQuestsSection = () => {
  const { pop } = useScreenNavigation();
  const { closeGuideQuestsScreen, openGuideQuestsScreen, showGuideQuests } =
    useManageGuideQuestsVisibility();

  const activeRef = useRef(false);
  const autoOpenedRef = useRef(false);
  const { gameData, setGuideQuestAttention } = useGameData();
  const userId = gameData?.character?.user_id ?? 0;
  const { data, loading } = useGuideQuestData();

  useEffect(() => {
    setGuideQuestAttention(data?.has_activity_attention ?? false);
  }, [data?.has_activity_attention, setGuideQuestAttention]);

  useEffect(() => {
    if (
      autoOpenedRef.current ||
      loading ||
      userId <= 0 ||
      (data?.quests.length ?? 0) === 0
    ) {
      return;
    }

    autoOpenedRef.current = true;
    openGuideQuestsScreen();
  }, [data?.quests.length, loading, openGuideQuestsScreen, userId]);

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
