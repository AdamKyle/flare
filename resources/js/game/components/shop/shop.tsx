import React from 'react';

import ShopProps from './types/shop-props';

import Card from 'ui/cards/card';
import ContainerWithTitle from 'ui/container/container-with-title';

const Shop = ({ close_shop }: ShopProps) => {
  return (
    <ContainerWithTitle manageSectionVisibility={close_shop} title={`Shop`}>
      <Card>
        <p className={'my-4 italic text-gray-800 dark:text-gray-300'}>
          Welcome to mu humble shop. What can I get you?
        </p>
      </Card>
    </ContainerWithTitle>
  );
};

export default Shop;
