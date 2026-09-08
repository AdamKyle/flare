import { Screens } from 'configuration/screen-manager/screen-manager-constants';
import {
  useBindScreen,
  useScreenNavigation,
} from 'configuration/screen-manager/screen-manager-kit';
import { ScreenPropsOf } from 'configuration/screen-manager/screen-manager-props';
import { useRef } from 'react';

import { useManageQuestLogVisibility } from '../../components/quests/hooks/use-manage-quest-log-visibility';
import { useQuestLogVisibility } from '../../components/quests/hooks/use-quest-log-visibility';

const BindQuestLog = () => {
  const { pop } = useScreenNavigation();
  const { showQuestLog } = useQuestLogVisibility();
  const { closeQuestLog } = useManageQuestLogVisibility();
  const activeRef = useRef(false);

  useBindScreen({
    when: showQuestLog,
    to: Screens.QUEST_LOG,
    props: (): ScreenPropsOf<typeof Screens.QUEST_LOG> => ({
      on_close: () => {
        if (activeRef.current) {
          pop();
        }
        closeQuestLog();
        activeRef.current = false;
      },
    }),
    mode: 'push',
    dedupeKey: 'quest-log',
  });

  return null;
};

export default BindQuestLog;
