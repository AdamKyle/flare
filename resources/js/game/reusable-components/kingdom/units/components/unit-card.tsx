import React, { ReactNode } from 'react';

import { isPositiveShowNumber } from '../../utils/show-detail-value';
import {
  unitCardBaseStyles,
  unitCardInteractiveStyles,
  unitCardSecondaryTextStyles,
} from '../styles/unit-card-styles';
import UnitCardProps from '../types/unit-card-props';

const UnitCard = ({
  unit_id: unitId,
  name,
  required_level: requiredLevel,
  attack,
  defence,
  on_open_unit: onOpenUnit,
}: UnitCardProps): ReactNode => {
  const renderContent = (): ReactNode => (
    <>
      <i className="ra ra-crossed-swords text-2xl" aria-hidden="true" />
      <span className="flex min-w-0 flex-1 flex-col gap-1">
        <span className="text-sm font-semibold break-words">{name}</span>
        <span
          className={`flex flex-col gap-0.5 text-xs ${unitCardSecondaryTextStyles()}`}
        >
          {isPositiveShowNumber(attack) && <span>Attack: {attack}</span>}
          {isPositiveShowNumber(defence) && <span>Defence: {defence}</span>}
          {isPositiveShowNumber(requiredLevel) && (
            <span>Required Building Level: {requiredLevel}</span>
          )}
        </span>
      </span>
    </>
  );

  if (onOpenUnit) {
    return (
      <button
        type="button"
        onClick={() => onOpenUnit(unitId)}
        aria-label={`Open Unit details for ${name}`}
        className={`${unitCardBaseStyles()} ${unitCardInteractiveStyles()}`}
      >
        {renderContent()}
      </button>
    );
  }

  return (
    <article aria-label={name} className={unitCardBaseStyles()}>
      {renderContent()}
    </article>
  );
};

export default UnitCard;
