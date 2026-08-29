import React, { ReactNode } from 'react';

import ItemFormContent from '../components/forms/item-form-content';
import ItemFormDefinition from '../api/definitions/item-form-definition';
import { ItemScreens } from '../screen-manager/item-screen-constants';
import { useItemScreenNavigation } from '../screen-manager/item-screen-kit';
import { ItemFormScreenProps } from '../screen-manager/item-screen-props';

const ItemFormScreen = ({
  item_id: itemId,
}: ItemFormScreenProps): ReactNode => {
  const navigation = useItemScreenNavigation();

  const handleSaved = (item: ItemFormDefinition): void => {
    navigation.replaceWith(ItemScreens.SHOW, { item_id: item.id });
  };

  const handleCancel = (): void => {
    navigation.pop();
  };

  return (
    <ItemFormContent
      item_id={itemId}
      on_saved={handleSaved}
      on_cancel={handleCancel}
      embedded={false}
    />
  );
};

export default ItemFormScreen;
