import React, { ReactNode } from 'react';

import RaceDetailProps from '../types/race-detail-props';

import Card from 'ui/cards/card';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const RaceDetail = ({ race }: RaceDetailProps): ReactNode => (
  <Card>
    <div className="flex flex-col gap-4 md:flex-row md:items-start">
      <img
        src={race.image_url}
        alt={`${race.name} portrait`}
        className="h-40 w-40 rounded-lg object-cover"
      />
      <div className="flex-1">
        <Dl>
          <Dt>Name</Dt>
          <Dd>{race.name}</Dd>
        </Dl>
        {race.description && (
          <p className="text-glacier-800 dark:text-glacier-200 mt-4">
            {race.description}
          </p>
        )}
      </div>
    </div>
  </Card>
);

export default RaceDetail;
