import React, { ReactNode } from 'react';

import ActivityStatusIndicator from './activity-status-indicator';
import ActivityContentProps from './types/activity-content-props';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import IconButton from 'ui/buttons/icon-button';

const ActivityContent = ({
  has_new_announcements,
  on_open_announcements,
  on_open_guide_quests,
  on_open_donations,
  on_action_selected,
}: ActivityContentProps): ReactNode => {
  const handleAction = (action: () => void): void => {
    on_action_selected();
    action();
  };

  const announcementsAriaLabel = has_new_announcements
    ? 'Announcements, new announcements available'
    : 'Announcements';

  const renderAnnouncementsIndicator = (): ReactNode => {
    if (!has_new_announcements) {
      return undefined;
    }

    return <ActivityStatusIndicator active={has_new_announcements} />;
  };

  return (
    <ul className="flex flex-col gap-3 px-4" aria-label="Activity actions">
      <li>
        <IconButton
          label="Announcements"
          variant={ButtonVariant.PRIMARY}
          on_click={() => handleAction(on_open_announcements)}
          additional_css="w-full"
          aria_label={announcementsAriaLabel}
          icon={renderAnnouncementsIndicator()}
        />
      </li>
      <li>
        <IconButton
          label="Guide Quest"
          variant={ButtonVariant.SUCCESS}
          on_click={() => handleAction(on_open_guide_quests)}
          additional_css="w-full"
        />
      </li>
      <li>
        <IconButton
          label="Donations"
          variant={ButtonVariant.DONATIONS}
          on_click={() => handleAction(on_open_donations)}
          additional_css="w-full"
          icon={
            <i className="fas fa-hand-holding-usd text-sm" aria-hidden="true" />
          }
          center_content
        />
      </li>
    </ul>
  );
};

export default ActivityContent;
