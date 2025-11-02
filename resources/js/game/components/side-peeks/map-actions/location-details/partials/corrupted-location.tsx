import React from 'react';

import { LocationInfoTypes } from '../enums/location-info-types';
import CorruptedLocationProps from '../types/partials/corrupted-location-props';

import Dd from 'ui/dl/dd';
import Dt from 'ui/dl/dt';

const CorruptedLocationDetails = ({
  is_corrupted,
  handle_on_info_click,
}: CorruptedLocationProps) => {
  const label = 'Is Location Corrupted?';

  return (
    <>
      <Dt>
        <button
          type="button"
          onClick={() => handle_on_info_click(LocationInfoTypes.CORRUPTED)}
          className="rounded p-1 text-gray-500 hover:text-gray-700 focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:text-gray-400 dark:hover:text-gray-200"
          aria-label={`More info about ${label}`}
        >
          <i className="fas fa-info-circle" aria-hidden="true" />
        </button>
        <span>{label}</span>
      </Dt>
      <Dd>{is_corrupted ? 'Yes' : 'No'}</Dd>
    </>
  );
};

export default CorruptedLocationDetails;
