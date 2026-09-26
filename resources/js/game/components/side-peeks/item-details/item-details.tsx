import React, { ReactNode } from 'react';

import ItemDetailsBody from './components/item-details-body';
import ItemDetailsProps from './types/item-details-props';

const ItemDetails = ({ item_id }: ItemDetailsProps): ReactNode => {
  return <ItemDetailsBody item_id={item_id} />;
};

export default ItemDetails;
