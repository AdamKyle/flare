import React from 'react';

import MarketProps from './types/market-props';

import Card from 'ui/cards/card';
import ContainerWithTitle from 'ui/container/container-with-title';

const Market = ({ close_shop }: MarketProps) => {
  return (
    <ContainerWithTitle manageSectionVisibility={close_shop} title={`Market`}>
      <Card>
        <p className={'my-4 italic text-gray-800 dark:text-gray-300'}>
          Show market jazz here.
        </p>
      </Card>
    </ContainerWithTitle>
  );
};

export default Market;
