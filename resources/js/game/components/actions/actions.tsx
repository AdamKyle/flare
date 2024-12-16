import React, { ReactNode } from 'react';

import IconSection from './partials/icon-section/icon-section';
import MonsterSection from './partials/monster-section';
import Card from '../../../ui/cards/card';

const Actions = (): ReactNode => {
  return (
    <Card>
      <div className="w-full flex flex-col lg:flex-row">
        <IconSection />

        <div className="flex flex-col items-center lg:items-start w-full">
          <MonsterSection />
        </div>
      </div>
    </Card>
  );
};

export default Actions;
