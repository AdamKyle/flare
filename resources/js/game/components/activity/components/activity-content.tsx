import clsx from 'clsx';
import React, { ReactNode } from 'react';

import ActivityContentProps from './types/activity-content-props';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import IconButton from 'ui/buttons/icon-button';

const ActivityContent = ({
  has_new_announcements,
  has_guide_quest_attention,
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

  const guideQuestAriaLabel = has_guide_quest_attention
    ? 'Guide Quest, ready to hand in'
    : 'Guide Quest';

  return (
    <ul className="flex flex-col gap-3 px-4" aria-label="Activity actions">
      <li>
        <IconButton
          label="Announcements"
          variant={ButtonVariant.PRIMARY}
          on_click={() => handleAction(on_open_announcements)}
          additional_css="w-full"
          aria_label={announcementsAriaLabel}
          icon={
            <i
              className={clsx('far fa-bell text-sm', {
                'text-mango-tango-600 dark:text-mango-tango-400 animate-pulse motion-reduce:animate-none':
                  has_new_announcements,
              })}
              aria-hidden="true"
            />
          }
        />
      </li>
      <li>
        <IconButton
          label="Guide Quest"
          variant={ButtonVariant.SUCCESS}
          on_click={() => handleAction(on_open_guide_quests)}
          additional_css="w-full"
          aria_label={guideQuestAriaLabel}
          icon={
            <i
              className={clsx('ra ra-scroll-unfurled text-sm', {
                'text-mango-tango-600 dark:text-mango-tango-400 animate-pulse motion-reduce:animate-none':
                  has_guide_quest_attention,
              })}
              aria-hidden="true"
            />
          }
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
