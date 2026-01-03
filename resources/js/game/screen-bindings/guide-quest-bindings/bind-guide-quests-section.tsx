import { Screens } from 'configuration/screen-manager/screen-manager-constants';
import {
  useBindScreen,
  useScreenNavigation,
} from 'configuration/screen-manager/screen-manager-kit';
import { ScreenPropsOf } from 'configuration/screen-manager/screen-manager-props';
import { useRef } from 'react';

import { useManageGuideQuestsVisibility } from '../../components/guide-quests/hooks/use-manage-guide-quests-visibility';

const BindGuideQuestsSection = () => {
  const { pop } = useScreenNavigation();
  const { closeGuideQuestsScreen, showGuideQuests } =
    useManageGuideQuestsVisibility();

  const activeRef = useRef(false);

  useBindScreen({
    when: showGuideQuests,
    to: Screens.DONATIONS,
    props: (): ScreenPropsOf<typeof Screens.DONATIONS> => ({
      on_close: () => {
        if (activeRef.current) {
          pop();
        }
        closeGuideQuestsScreen();
        activeRef.current = false;
      },
    }),
    mode: 'push',
    dedupeKey: 'shop',
  });

  return null;
};

export default BindGuideQuestsSection;
