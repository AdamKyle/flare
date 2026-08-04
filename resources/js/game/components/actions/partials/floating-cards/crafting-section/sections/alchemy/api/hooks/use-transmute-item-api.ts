import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError } from 'axios';
import { useState } from 'react';

import AlchemyApiResponseDefinition from '../definitions/alchemy-api-response-definition';
import TransmuteItemRequestDefinition from '../definitions/transmute-item-request-definition';
import { AlchemyApiUrls } from '../enums/alchemy-api-urls';
import UseTransmuteItemApiDefinition from './definitions/use-transmute-item-api-definition';
import UseTransmuteItemApiParams from './definitions/use-transmute-item-api-params';

export const useTransmuteItemApi = ({
  characterId,
  itemId,
}: UseTransmuteItemApiParams): UseTransmuteItemApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const [transmuting, setTransmuting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const transmute = async (): Promise<AlchemyApiResponseDefinition | null> => {
    if (!itemId) {
      return null;
    }

    setTransmuting(true);
    setError(null);

    try {
      const request: TransmuteItemRequestDefinition = {
        item_to_craft: itemId,
      };

      return await apiHandler.post<
        AlchemyApiResponseDefinition,
        never,
        TransmuteItemRequestDefinition
      >(getUrl(AlchemyApiUrls.TRANSMUTE, { character: characterId }), request);
    } catch (requestError) {
      setError(
        requestError instanceof AxiosError
          ? (requestError.response?.data?.message ??
              'Unable to transmute the item.')
          : 'Unable to transmute the item.'
      );

      return null;
    } finally {
      setTransmuting(false);
    }
  };

  return { transmuting, error, transmute };
};
