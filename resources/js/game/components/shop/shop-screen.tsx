import React from 'react';

import { ShopProvider } from './context/shop-context';
import Shop from './shop';
import ShopScreenProps from './types/shop-screen-props';

const ShopScreen = ({ close_shop }: ShopScreenProps) => {
  return (
    <ShopProvider>
      <Shop close_shop={close_shop} />
    </ShopProvider>
  );
};

export default ShopScreen;
