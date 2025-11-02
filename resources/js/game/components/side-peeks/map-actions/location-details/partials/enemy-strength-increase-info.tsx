import React from 'react';

import LocationInfoSectionProps from '../types/partials/location-info-section-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const EnemyStrengthIncreaseInfo = ({
  handel_close_info_section,
}: LocationInfoSectionProps) => {
  return (
    <div className={'prose dark:prose-dark px-4 py-2 dark:text-white'}>
      <h2>Enemy Strength Increase</h2>
      <p>
        When it comes to a location increasing the strength of an enemy this
        will apply to all aspects of the enemy you choose to fight from the drop
        down. This does not apply to raid monsters when the location becomes
        corrupted or to raid bosses who appear at the location when its
        corrupted.
      </p>
      <p>
        Some aspects wont go above the max or be effected at all, lets take a
        closer look at those now:
      </p>
      <ul>
        <li>
          <strong className={'dark:text-white'}>Skills</strong>: Will not go
          above 100% regardless of the enemy strength increase.
        </li>
        <li>
          <strong className={'dark:text-white'}>
            Devouring Light/Darkness
          </strong>
          : Will not go above 75%
        </li>
        <li>
          <strong className={'dark:text-white'}>Ambush & Counter</strong>: Will
          Not go above 90%
        </li>
      </ul>
      <Button
        on_click={handel_close_info_section}
        label={'Close'}
        variant={ButtonVariant.PRIMARY}
        additional_css={'w-full'}
      />
    </div>
  );
};

export default EnemyStrengthIncreaseInfo;
