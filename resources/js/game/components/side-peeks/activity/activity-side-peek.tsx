import React, { ReactNode } from 'react';

import ActivityContent from '../../activity/components/activity-content';
import { useActivityActions } from '../../activity/hooks/use-activity-actions';
import { useCloseSidePeekEmitter } from '../base/hooks/use-close-side-peek-emitter';

const ActivitySidePeek = (): ReactNode => {
  const { closeSidePeek } = useCloseSidePeekEmitter();
  const {
    has_new_announcements,
    open_announcements,
    open_guide_quests,
    open_donations,
  } = useActivityActions();

  return (
    <div className="py-4">
      <ActivityContent
        has_new_announcements={has_new_announcements}
        on_open_announcements={open_announcements}
        on_open_guide_quests={open_guide_quests}
        on_open_donations={open_donations}
        on_action_selected={closeSidePeek}
      />
    </div>
  );
};

export default ActivitySidePeek;
