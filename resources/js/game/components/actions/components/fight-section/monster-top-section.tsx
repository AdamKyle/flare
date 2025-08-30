import React, { ReactNode } from 'react';

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

  return (
    <>
      <img
        src={img_src}
        alt="A cute cat"
        className="
                    mx-auto mt-4 rounded-md drop-shadow-md
                    sm:w-64 md:w-72 lg:w-80
                    transition-all duration-300 ease-in-out transform hover:scale-105
                    dark:drop-shadow-lg dark:border dark:border-gray-700 dark:bg-gray-800
                    focus:outline-none focus:ring-2 focus:ring-danube-500 focus:ring-offset-2 focus:ring-offset-white
                "
      />
      <div
        className="
                    mx-auto mt-4 flex items-center justify-center
                    w-full gap-x-3 text-lg leading-none
                "
      >
        <button
          className="text-xl transition-all duration-300 ease-in-out transform hover:scale-105 hover:text-gray-700 dark:hover:text-gray-500"
          aria-label="Previous"
          onClick={handleMovePrevious}
        >
          <i className="fas fa-chevron-circle-left" aria-hidden="true"></i>
        </button>
        <span className="font-semibold">{monster_name}</span>
        <button
          className="text-xl transition-all duration-300 ease-in-out transform hover:scale-105 hover:text-gray-700 dark:hover:text-gray-500"
          aria-label="Next"
          onClick={handleMoveNext}
        >
          <i className="fas fa-chevron-circle-right" aria-hidden="true"></i>
        </button>
      </div>

      <div
        className="
                    mx-auto mt-4 flex items-center justify-center
                    w-full lg:w-1/3 gap-x-3 text-lg leading-none
                "
      >
        <LinkButton
          label="View Stats"
          variant={ButtonVariant.PRIMARY}
          on_click={() => view_monster_stats()}
        />
      </div>
    </>
  );
};

export default MonsterTopSection;
