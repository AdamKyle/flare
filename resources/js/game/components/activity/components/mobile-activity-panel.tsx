import React, { ReactNode } from 'react';

import ActivityContent from './activity-content';
import MobileActivityPanelProps from './types/mobile-activity-panel-props';
import { useActivityActions } from '../hooks/use-activity-actions';

import MobilePanel from 'ui/mobile-panel/mobile-panel';

const MobileActivityPanel = ({
  on_close,
}: MobileActivityPanelProps): ReactNode => {
  const {
    has_new_announcements,
    open_announcements,
    open_guide_quests,
    open_donations,
  } = useActivityActions();

  return (
    <MobilePanel title="Activity" on_close={on_close}>
      <ActivityContent
        has_new_announcements={has_new_announcements}
        on_open_announcements={open_announcements}
        on_open_guide_quests={open_guide_quests}
        on_open_donations={open_donations}
        on_action_selected={on_close}
      />
    </MobilePanel>
  );
};

export default MobileActivityPanel;
