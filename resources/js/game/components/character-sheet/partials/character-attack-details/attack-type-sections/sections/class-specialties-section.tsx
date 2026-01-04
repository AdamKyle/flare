import React from 'react';

import ClassSpecialitiesSectionProps from './types/class-speciailties-section-props';

const ClassSpecialtiesSection = ({
  class_specialties,
}: ClassSpecialitiesSectionProps) => {
  if (!class_specialties || class_specialties.length <= 0) {
    return null;
  }

  const listElements = class_specialties.map((classSpecialty) => {
    return (
      <li>
        <strong>{classSpecialty.name}</strong>{' '}
        <span className="text-green-700 dark:text-green-500">
          +{(classSpecialty.amount * 100).toFixed(2)}%
        </span>
      </li>
    );
  });

  return (
    <div className={'my-2'}>
      <h4 className={'text-marigold-700 dark:text-marigold-500 my-2 font-bold'}>
        Class Specialities
      </h4>
      <ol className="ml-2 list-inside list-decimal space-y-4 text-gray-500 dark:text-gray-400">
        {listElements}
      </ol>
    </div>
  );
};

export default ClassSpecialtiesSection;
