import { useCallback } from 'react';

import UseOpenItemDetailsSidePeekDefinition from './definitions/use-open-item-details-side-peek-definition';
import { SidePeekComponentRegistrationEnum } from '../../base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../base/event-types/side-peek';
import { useSidePeekEmitter } from '../../base/hooks/use-side-peek-emitter';

export const useOpenItemDetailsSidePeek =
  (): UseOpenItemDetailsSidePeekDefinition => {
    const sidePeekEmitter = useSidePeekEmitter();

    const openItemDetails = useCallback(
      (itemId: number, title: string) => {
        sidePeekEmitter.emit(
          SidePeek.SIDE_PEEK,
          SidePeekComponentRegistrationEnum.ITEM_DETAILS,
          {
            is_open: true,
            title,
            allow_clicking_outside: true,
            item_id: itemId,
          }
        );
      },
      [sidePeekEmitter]
    );

    return { openItemDetails };
  };
