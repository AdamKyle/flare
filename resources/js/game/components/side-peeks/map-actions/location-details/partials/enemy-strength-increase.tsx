import { isNil } from 'lodash';
import React from 'react';

import { LocationInfoTypes } from '../enums/location-info-types';
import EnemyStrengthIncreaseProps from '../types/partials/enemy-strength-increase-props';

import Dd from 'ui/dl/dd';
import Dt from 'ui/dl/dt';

const EnemyStrengthIncrease = ({
  enemy_strength_increase,
  handle_on_info_click,
}: EnemyStrengthIncreaseProps) => {
  const label = 'Enemy Strength Increase';
  const hasValue = !isNil(enemy_strength_increase);

  return (
    <>
      <Dt>
        <button
          type="button"
          onClick={() =>
            handle_on_info_click(LocationInfoTypes.ENEMY_STRENGTH_INCREASE)
          }
          className="p-1 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500
                       text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
          aria-label={`More info about ${label}`}
        >
          <i className="fas fa-info-circle" aria-hidden="true" />
        </button>
        <span>{label}:</span>
      </Dt>
      <Dd>
        {hasValue ? `+${(enemy_strength_increase! * 100).toFixed(0)}%` : 'None'}
      </Dd>
    </>
  );
};

export default EnemyStrengthIncrease;
