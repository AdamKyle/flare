import React, { ReactNode } from 'react';

import ItemFormSidePeekProps from './types/item-form-side-peek-props';
import ItemFormContent from '../forms/item-form-content';

import { useCloseSidePeekEmitter } from '../../../../game/components/side-peeks/base/hooks/use-close-side-peek-emitter';

const ItemFormSidePeek = ({
  item_id: itemId,
  on_saved: onSaved,
}: ItemFormSidePeekProps): ReactNode => {
  const { closeSidePeek } = useCloseSidePeekEmitter();

  const handleSaved: ItemFormSidePeekProps['on_saved'] = (item) => {
    onSaved(item);
    closeSidePeek();
  };

  return (
    <ItemFormContent
      item_id={itemId}
      on_saved={handleSaved}
      on_cancel={closeSidePeek}
      embedded
    />
  );
};

export default ItemFormSidePeek;
