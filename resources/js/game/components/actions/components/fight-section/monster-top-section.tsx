import React, { ReactNode } from 'react';

import Notifications from '../../../notifications/notifications';
import MonsterNamePicker from './partials/monster-name-picker';
import MonsterTopSectionProps from './types/monster-top-section-props';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LinkButton from 'ui/buttons/link-button';

const MonsterTopSection = ({
  next_action,
  prev_action,
  monster_name,
  img_src,
  view_monster_stats,
  total_monsters,
  current_index,
  monsters,
  select_action,
}: MonsterTopSectionProps): ReactNode => {
  const handleMoveNext = () => {
    let newIndex = current_index + 1;

    if (newIndex > total_monsters) {
      newIndex = total_monsters;
    }

    next_action(newIndex);
  };

  const handleMovePrevious = () => {
    let newIndex = current_index - 1;

    if (newIndex < 0) {
      newIndex = 0;
    }

    prev_action(newIndex);
  };

  const handleViewMonsterStats = () => {
    const monsterId = monsters[current_index].id;

    view_monster_stats(monsterId || 0);
  };

  return (
    <>
      <img
        src={img_src}
        alt=""
        className="focus:ring-danube-500 mx-auto mt-4 transform rounded-md drop-shadow-md transition-all duration-300 ease-in-out hover:scale-105 focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:outline-none sm:w-64 md:w-72 lg:w-80 dark:border dark:border-gray-700 dark:bg-gray-800 dark:drop-shadow-lg"
      />
      <div className="mx-auto mt-4 flex w-full items-center justify-center gap-x-3 text-lg leading-none">
        <button
          className="transform text-xl transition-all duration-300 ease-in-out hover:scale-105 hover:text-gray-700 dark:hover:text-gray-500"
          aria-label="Previous"
          onClick={handleMovePrevious}
          type="button"
        >
          <i className="fas fa-chevron-circle-left" aria-hidden="true"></i>
        </button>

        <div className="w-2/3 max-w-[28ch] min-w-[16ch] sm:w-full">
          <MonsterNamePicker
            display_name={monster_name || ''}
            monsters={monsters}
            current_index={current_index}
            on_select={select_action}
          />
        </div>

        <button
          className="transform text-xl transition-all duration-300 ease-in-out hover:scale-105 hover:text-gray-700 dark:hover:text-gray-500"
          aria-label="Next"
          onClick={handleMoveNext}
          type="button"
        >
          <i className="fas fa-chevron-circle-right" aria-hidden="true"></i>
        </button>
      </div>

      <div className="mx-auto mt-4 flex w-full items-center justify-center gap-x-3 text-lg leading-none lg:w-1/3">
        <LinkButton
          label="View Stats"
          variant={ButtonVariant.PRIMARY}
          on_click={handleViewMonsterStats}
        />
      </div>
      <div className={'mt-2'}>
        <Notifications />
      </div>
    </>
  );
};

export default MonsterTopSection;
