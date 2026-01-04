import React from 'react';

import ClassMasteriesSectionProps from './types/class-masteries-section-props';

const ClassMasteriesSection = ({
  class_masteries,
}: ClassMasteriesSectionProps) => {
  if (!class_masteries || class_masteries.length <= 0) {
    return null;
  }

  const listElements = class_masteries.map((classMastery) => {
    return (
      <li>
        <strong>{classMastery.name}</strong>{' '}
        <span className="text-green-700 dark:text-green-500">
          +{(classMastery.amount * 100).toFixed(2)}%
        </span>
      </li>
    );
  });

  return (
    <div className={'my-2'}>
      <h4 className={'text-marigold-700 dark:text-marigold-500 my-2 font-bold'}>
        Class Masteries
      </h4>
      <ol className="ml-2 list-inside list-decimal space-y-4 text-gray-500 dark:text-gray-400">
        {listElements}
      </ol>
    </div>
  );
};

export default ClassMasteriesSection;
