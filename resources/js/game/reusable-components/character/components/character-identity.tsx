import clsx from 'clsx';
import React, { ReactNode } from 'react';

import CharacterIdentityProps from '../types/character-identity-props';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LinkButton from 'ui/buttons/link-button';

const CharacterIdentity = ({
  race_id: raceId,
  race_name: raceName,
  class_id: classId,
  class_name: className,
  on_open_race: onOpenRace,
  on_open_class: onOpenClass,
  inline = false,
}: CharacterIdentityProps): ReactNode => {
  return (
    <div className="grid grid-cols-2 gap-4">
      <div className={clsx('min-w-0', inline && 'flex items-center gap-1')}>
        <span className="text-sm font-semibold text-gray-700 dark:text-gray-300">
          Race{inline ? ':' : ''}
        </span>
        <LinkButton
          label={raceName}
          variant={ButtonVariant.PRIMARY}
          on_click={() => onOpenRace(raceId, raceName)}
          aria_label={`View ${raceName} race details`}
          additional_css="max-w-full text-left break-words"
        />
      </div>
      <div className={clsx('min-w-0', inline && 'flex items-center gap-1')}>
        <span className="text-sm font-semibold text-gray-700 dark:text-gray-300">
          Class{inline ? ':' : ''}
        </span>
        <LinkButton
          label={className}
          variant={ButtonVariant.PRIMARY}
          on_click={() => onOpenClass(classId, className)}
          aria_label={`View ${className} class details`}
          additional_css="max-w-full text-left break-words"
        />
      </div>
    </div>
  );
};

export default CharacterIdentity;
