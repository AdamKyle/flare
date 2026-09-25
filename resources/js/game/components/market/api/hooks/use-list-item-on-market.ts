import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosRequestConfig } from 'axios';
import { useCallback } from 'react';

import { useMarketMutation } from './use-market-mutation';
import UseListItemOnMarketDefinition from '../definitions/use-list-item-on-market-definition';
import UseListItemOnMarketRequestDefinition from '../definitions/use-list-item-on-market-request-definition';
import UseListItemOnMarketRequestParamsDefinition from '../definitions/use-list-item-on-market-request-params-definition';
import UseListItemOnMarketResponseDefinition from '../definitions/use-list-item-on-market-response-definition';
import { MarketApis } from '../enums/market-apis';

export const useListItemOnMarket = ({
  character_id: characterId,
}: UseListItemOnMarketRequestParamsDefinition): UseListItemOnMarketDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { loading, error, run } =
    useMarketMutation<UseListItemOnMarketResponseDefinition>(
      'Unable to list this item on the Market.'
    );

  const listItem = useCallback(
    (slotId: number, listFor: number) =>
      run((signal) =>
        apiHandler.post<
          UseListItemOnMarketResponseDefinition,
          AxiosRequestConfig<UseListItemOnMarketResponseDefinition>,
          UseListItemOnMarketRequestDefinition
        >(
          getUrl(MarketApis.LIST_ITEM_ON_MARKET, { character: characterId }),
          { slot_id: slotId, list_for: listFor },
          { signal }
        )
      ),
    [apiHandler, getUrl, run, characterId]
  );

  return { loading, error, list_item: listItem };
};
