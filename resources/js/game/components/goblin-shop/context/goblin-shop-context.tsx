import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import React, { createContext } from 'react';

import GoblinShopContextDefinition from './definitions/goblin-shop-context-definition';
import GoblinShopProviderProps from './types/goblin-shop-provider-props';
import BaseUsableItemDefinition from '../../../api-definitions/items/usable-item-definitions/base-usable-item-definition';
import { useInfiniteScroll } from '../../character-sheet/partials/character-inventory/hooks/use-infinite-scroll';
import { GoblinShopApiUrls } from '../api/enums/goblin-shop-api-urls';

const GoblinShopContext = createContext<
  GoblinShopContextDefinition | undefined
>(undefined);

const GoblinShopProvider = ({
  characterId,
  children,
}: GoblinShopProviderProps) => {
  const { data, loading, error, onEndReached } =
    UsePaginatedApiHandler<BaseUsableItemDefinition>({
      url: GoblinShopApiUrls.VISIT_SHOP,
      urlParams: { character: characterId },
    });

  const { handleScroll } = useInfiniteScroll({
    on_end_reached: onEndReached,
  });

  return (
    <GoblinShopContext.Provider
      value={{
        data,
        loading,
        error,
        handleScroll,
      }}
    >
      {children}
    </GoblinShopContext.Provider>
  );
};

export { GoblinShopProvider, GoblinShopContext };
