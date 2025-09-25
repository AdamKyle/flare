import React from 'react';

import ShopCardDetails from './shop-card-details';
import ShopItemViewProps from '../types/shop-item-views-props';

import Card from 'ui/cards/card';
import ContainerWithTitle from 'ui/container/container-with-title';

const ShopItemView = ({ item, close_view }: ShopItemViewProps) => {
  return (
    <ContainerWithTitle
      manageSectionVisibility={close_view}
      title={`Viewing: ${item.name}`}
    >
      <Card>
        <ShopCardDetails item={item} />
      </Card>
    </ContainerWithTitle>
  );
};

export default ShopItemView;
