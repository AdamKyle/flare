import React from 'react';

import ClassBonusAttributesProps from './types/class-bonus-attributes-props';

const ClassBonusAttributesSection = ({
  class_bonus_details,
}: ClassBonusAttributesProps) => {
  if (!class_bonus_details) {
    return null;
  }

  return (
    <div className={'mb-2'}>
      <h4 className={'text-marigold-700 dark:text-marigold-500 my-2 font-bold'}>
        Class Bonus Attributes
      </h4>
      <ol className="ml-2 list-inside list-decimal space-y-4 text-gray-500 dark:text-gray-400">
        <li>
          <strong>{class_bonus_details.name}</strong>{' '}
          <span className="text-green-700 dark:text-green-500">
            +{(class_bonus_details.amount * 100).toFixed(2)}%
          </span>
        </li>
      </ol>
    </div>
  );
};

export default ClassBonusAttributesSection;
