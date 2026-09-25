import { useCallback } from 'react';

import UseOpenMarketItemDetailsDefinition from './definitions/use-open-market-item-details-definition';
import { SidePeekComponentRegistrationEnum } from '../../side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../side-peeks/base/hooks/use-side-peek-emitter';

export const useOpenMarketItemDetails =
  (): UseOpenMarketItemDetailsDefinition => {
    const sidePeekEmitter = useSidePeekEmitter();

    const openItemDetails = useCallback(
      (itemId: number) => {
        sidePeekEmitter.emit(
          SidePeek.SIDE_PEEK,
          SidePeekComponentRegistrationEnum.ITEM_DETAILS,
          {
            is_open: true,
            title: 'Item Details',
            allow_clicking_outside: true,
            item_id: itemId,
          }
        );
      },
      [sidePeekEmitter]
    );

    return { open_item_details: openItemDetails };
  };
