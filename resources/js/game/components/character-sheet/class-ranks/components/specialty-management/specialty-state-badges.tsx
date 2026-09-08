import React, { ReactNode } from 'react';

import SpecialtyStateBadgesProps from './types/specialty-state-badges-props';

const badgeBaseStyles =
  'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap';

const damageStyles =
  'bg-wisp-pink-100 text-wisp-pink-800 dark:bg-wisp-pink-900/60 dark:text-wisp-pink-200';

const passiveStyles =
  'bg-glacier-100 text-glacier-800 dark:bg-glacier-900/60 dark:text-glacier-200';

const equippedStyles =
  'bg-danube-100 text-danube-800 dark:bg-danube-900/60 dark:text-danube-200';

const masteredStyles =
  'bg-de-york-100 text-de-york-800 dark:bg-de-york-900/60 dark:text-de-york-200';

const inProgressStyles =
  'bg-glacier-100 text-glacier-800 dark:bg-glacier-900/60 dark:text-glacier-200';

const availableStyles =
  'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';

const lockedStyles =
  'bg-mango-tango-100 text-mango-tango-800 dark:bg-mango-tango-900/60 dark:text-mango-tango-200';

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
