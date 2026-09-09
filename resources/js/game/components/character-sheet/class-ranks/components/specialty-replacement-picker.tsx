import React, { ReactNode } from 'react';

import SpecialtyReplacementPickerProps from './types/specialty-replacement-picker-props';

const SpecialtyReplacementPicker = ({
  equipped_specialties: equippedSpecialties,
  selected_id: selectedId,
  on_select: onSelect,
}: SpecialtyReplacementPickerProps): ReactNode => {
  return (
    <fieldset className="flex flex-col gap-2">
      <legend className="mb-1 text-sm font-semibold text-gray-900 dark:text-gray-100">
        Choose a Specialty to replace
      </legend>
      {equippedSpecialties.map((specialEquipped) => {
        const id = `replace-specialty-${specialEquipped.id}`;

        return (
          <label
            key={specialEquipped.id}
            htmlFor={id}
            className="border-glacier-300 dark:border-glacier-700 hover:bg-glacier-100 dark:hover:bg-glacier-800 flex cursor-pointer items-center gap-2 rounded-md border p-2"
          >
            <input
              id={id}
              type="radio"
              name="specialty-replacement"
              checked={selectedId === specialEquipped.id}
              onChange={() => onSelect(specialEquipped.id)}
              className="text-danube-600 focus:ring-danube-500 h-4 w-4"
            />
            <span className="text-sm text-gray-900 dark:text-gray-100">
              {specialEquipped.class_mastery.name}
            </span>
          </label>
        );
      })}
    </fieldset>
  );
};

export default SpecialtyReplacementPicker;
