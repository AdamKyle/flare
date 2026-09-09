import React, { ReactNode } from 'react';

import SpecialtyStateBadgesProps from './types/specialty-state-badges-props';

const badgeBaseStyles =
  'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap';

const damageStyles =
  'bg-wisp-pink-200 text-wisp-pink-900 dark:bg-wisp-pink-200 dark:text-wisp-pink-900';

const passiveStyles =
  'bg-glacier-200 text-glacier-900 dark:bg-glacier-200 dark:text-glacier-900';

const equippedStyles =
  'bg-danube-200 text-danube-900 dark:bg-danube-200 dark:text-danube-900';

const masteredStyles =
  'bg-de-york-200 text-de-york-900 dark:bg-de-york-200 dark:text-de-york-900';

const inProgressStyles =
  'bg-glacier-200 text-glacier-900 dark:bg-glacier-200 dark:text-glacier-900';

const availableStyles =
  'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';

const lockedStyles =
  'bg-mango-tango-200 text-mango-tango-900 dark:bg-mango-tango-200 dark:text-mango-tango-900';

const SpecialtyStateBadges = (props: SpecialtyStateBadgesProps): ReactNode => (
  <div className="flex flex-wrap items-center gap-1">
    <span
      className={`${badgeBaseStyles} ${props.is_damage ? damageStyles : passiveStyles}`}
    >
      {props.is_damage ? 'Damage' : 'Passive'}
    </span>
    {props.is_equipped && (
      <span className={`${badgeBaseStyles} ${equippedStyles}`}>Equipped</span>
    )}
    {props.is_mastered && (
      <span className={`${badgeBaseStyles} ${masteredStyles}`}>Mastered</span>
    )}
    {props.is_in_progress && (
      <span className={`${badgeBaseStyles} ${inProgressStyles}`}>
        In Progress
      </span>
    )}
    {props.is_available && (
      <span className={`${badgeBaseStyles} ${availableStyles}`}>Available</span>
    )}
    {props.is_locked_level && (
      <span className={`${badgeBaseStyles} ${lockedStyles}`}>
        Requires Class Level {props.required_class_level}
      </span>
    )}
  </div>
);

export default SpecialtyStateBadges;
