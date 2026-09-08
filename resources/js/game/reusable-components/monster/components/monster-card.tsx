import React, { ReactNode } from 'react';

import { isLocationType, LOCATION_TYPE_LABELS } from '../enums/location-type';
import MonsterCardProps from '../types/monster-card-props';

const resolveCategoryLabel = ({
  is_celestial_entity: isCelestialEntity,
  is_raid_boss: isRaidBoss,
  is_raid_monster: isRaidMonster,
  only_for_location_type: onlyForLocationType,
}: MonsterCardProps): string => {
  if (isCelestialEntity) {
    return 'Celestial';
  }

  if (isRaidBoss) {
    return 'Raid Boss';
  }

  if (isRaidMonster) {
    return 'Raid Monster';
  }

  if (onlyForLocationType !== null) {
    return isLocationType(onlyForLocationType)
      ? LOCATION_TYPE_LABELS[onlyForLocationType]
      : 'Special Location';
  }

  return 'Regular';
};

const MonsterCard = (props: MonsterCardProps): ReactNode => {
  const { monster_id: monsterId, name, on_open_monster: onOpenMonster } = props;

  return (
    <button
      type="button"
      onClick={() => onOpenMonster(monsterId)}
      aria-label={`Open Monster details for ${name}`}
      className="border-glacier-300 bg-glacier-100 hover:bg-glacier-200 focus-visible:ring-glacier-500 w-full rounded-lg border-2 p-3 text-left shadow-sm transition-colors focus:outline-none focus-visible:ring-2"
    >
      <div className="flex items-start gap-3">
        <i
          className="fas fa-skull text-glacier-700 text-2xl"
          aria-hidden="true"
        />
        <div className="flex min-w-0 flex-col gap-1">
          <p className="text-glacier-900 font-medium break-words">{name}</p>
          <p className="text-glacier-700 text-xs">
            {resolveCategoryLabel(props)}
          </p>
        </div>
      </div>
    </button>
  );
};

export default MonsterCard;
