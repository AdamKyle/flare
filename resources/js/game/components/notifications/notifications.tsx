import clsx from 'clsx';
import React, { useId, useState } from 'react';

import { UseManageAnnouncementsVisibility } from '../announcements/hooks/use-manage-announcements-visibility';
import { useManageDonationsVisibility } from '../donations/hooks/use-manage-donations-visibility';
import { useManageGuideQuestsVisibility } from '../guide-quests/hooks/use-manage-guide-quests-visibility';

import { useGameData } from 'game-data/hooks/use-game-data';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import IconButton from 'ui/buttons/icon-button';

const Notifications = () => {
  const { gameData, markAnnouncementsSeen } = useGameData();
  const { openAnnouncements } = UseManageAnnouncementsVisibility();
  const { openDonationScreen } = useManageDonationsVisibility();
  const { openGuideQuestsScreen } = useManageGuideQuestsVisibility();
  const [isExpanded, setIsExpanded] = useState(false);
  const contentId = useId();

  const handleToggle = () => {
    setIsExpanded((previousIsExpanded) => !previousIsExpanded);
  };

  const chevronClassName = clsx(
    'fas fa-chevron-down ml-2 h-3 w-3 transition-transform duration-200',
    isExpanded ? 'rotate-180' : 'rotate-0'
  );

  const handleAnnouncementClick = () => {
    markAnnouncementsSeen();

    openAnnouncements();
  };

  const renderIcon = () => {
    if (!gameData?.hasNewAnnouncements) {
      return null;
    }

    return (
      <i
        className="far fa-bell text-mango-tango-600 dark:text-mango-tango-500 mr-2"
        aria-hidden="true"
      />
    );
  };

  const renderHeader = () => {
    let title = 'Things are happening!';

    if (!gameData?.hasNewAnnouncements) {
      title = 'A place of action';
    }

    return (
      <button
        type="button"
        className="border-danube-300 bg-regent-st-blue-50 text-regent-st-blue-900 dark:border-danube-700 dark:bg-regent-st-blue-900 dark:text-regent-st-blue-50 flex w-full items-center justify-between border-b-1 px-3 py-1.5 text-left font-semibold"
        aria-expanded={isExpanded}
        aria-controls={contentId}
        onClick={handleToggle}
      >
        <span className="inline-flex items-center">
          {renderIcon()}
          <span>{title}</span>
        </span>
        <i className={chevronClassName} aria-hidden="true" />
      </button>
    );
  };

  const renderContent = () => {
    const contentClassName = clsx(
      'overflow-hidden px-3 transition-[max-height,opacity] duration-300 ease-in-out',
      isExpanded ? 'max-h-32 opacity-100 pb-3 pt-2' : 'max-h-0 opacity-0 py-0'
    );

    const renderAnnouncementsIcon = () => {
      if (!gameData?.hasNewAnnouncements) {
        return null;
      }

      return (
        <i
          className="far fa-bell text-mango-tango-300 dark:text-mango-tango-400"
          aria-hidden="true"
        />
      );
    };

    return (
      <div
        id={contentId}
        aria-hidden={!isExpanded}
        className={contentClassName}
      >
        <div className="flex flex-col gap-2 sm:flex-row">
          <IconButton
            label="Announcements"
            variant={ButtonVariant.PRIMARY}
            on_click={handleAnnouncementClick}
            additional_css="w-full sm:flex-1"
            icon={renderAnnouncementsIcon()}
          />
          <IconButton
            label="Guide Quest"
            variant={ButtonVariant.SUCCESS}
            on_click={openGuideQuestsScreen}
            additional_css="w-full sm:flex-1"
          />
        </div>
        <div className="mt-2 w-full text-center">
          <IconButton
            on_click={openDonationScreen}
            variant={ButtonVariant.DONATIONS}
            label="Donations"
            additional_css="w-full md:w-2/3"
            icon={<i className="fas fa-hand-holding-usd" />}
            center_content
          />
        </div>
      </div>
    );
  };

  return (
    <div
      className={clsx(
        'border-danube-300 bg-danube-100 dark:border-danube-700 dark:bg-danube-900 mx-auto ' +
          'rounded-md border-1 transition-all duration-300 ease-in-out',
        isExpanded ? 'w-full max-w-md' : 'w-full max-w-xs'
      )}
    >
      {renderHeader()}
      {renderContent()}
    </div>
  );
};

export default Notifications;
