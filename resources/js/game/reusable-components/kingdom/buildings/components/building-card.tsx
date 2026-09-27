import React, { ReactNode } from 'react';

import { isPositiveShowNumber } from '../../utils/show-detail-value';
import {
  buildingCardBaseStyles,
  buildingCardInteractiveStyles,
  buildingCardSecondaryTextStyles,
} from '../styles/building-card-styles';
import BuildingCardProps from '../types/building-card-props';

const BuildingCard = ({
  building_id: buildingId,
  name,
  required_level: requiredLevel,
  description,
  on_open_building: onOpenBuilding,
}: BuildingCardProps): ReactNode => {
  const renderContent = (): ReactNode => (
    <>
      <i className="ra ra-tower text-2xl" aria-hidden="true" />
      <span className="flex min-w-0 flex-1 flex-col gap-1">
        <span className="text-sm font-semibold break-words">{name}</span>
        {description && (
          <span className={`text-xs ${buildingCardSecondaryTextStyles()}`}>
            {description}
          </span>
        )}
        {isPositiveShowNumber(requiredLevel) && (
          <span className={`text-xs ${buildingCardSecondaryTextStyles()}`}>
            Required Building Level: {requiredLevel}
          </span>
        )}
      </span>
    </>
  );

  if (onOpenBuilding) {
    return (
      <button
        type="button"
        onClick={() => onOpenBuilding(buildingId)}
        aria-label={`Open Building details for ${name}`}
        className={`${buildingCardBaseStyles()} ${buildingCardInteractiveStyles()}`}
      >
        {renderContent()}
      </button>
    );
  }

  return (
    <article aria-label={name} className={buildingCardBaseStyles()}>
      {renderContent()}
    </article>
  );
};

export default BuildingCard;
