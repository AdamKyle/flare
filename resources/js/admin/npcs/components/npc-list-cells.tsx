import React, { ReactNode } from 'react';

import NpcListDefinition from '../api/definitions/npc-list-definition';

export const renderNpcNameCell = (row: NpcListDefinition): ReactNode => (
  <span className="text-glacier-900 dark:text-glacier-100 font-medium">
    {row.real_name}
  </span>
);
