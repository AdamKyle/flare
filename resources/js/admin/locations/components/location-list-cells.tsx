import React, { ReactNode } from 'react';

import LocationListDefinition from '../api/definitions/location-list-definition';

export const renderLocationNameCell = (
  row: LocationListDefinition
): ReactNode => (
  <span className="text-glacier-900 dark:text-glacier-100 font-medium">
    {row.name}
  </span>
);
