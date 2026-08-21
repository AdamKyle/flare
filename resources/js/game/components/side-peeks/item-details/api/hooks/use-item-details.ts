import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import ItemDetailsResponseDefinition from '../definitions/item-details-response-definition';
import { ItemDetailsApiUrls } from '../enums/item-details-api-urls';
import UseItemDetailsDefinition from './definitions/use-item-details-definition';

export const useItemDetails = (itemId: number): UseItemDetailsDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const [data, setData] = useState<ItemDetailsResponseDefinition | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    if (itemId <= 0) {
      return;
    }

    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    const fetchItemDetails = async () => {
      try {
        const result = await apiHandler.get<
          ItemDetailsResponseDefinition,
          never
        >(getUrl(ItemDetailsApiUrls.ITEM_DETAILS, { item: itemId }), {
          signal: controller.signal,
        });

        setData(result);
      } catch (requestError) {
        if (axios.isCancel(requestError)) {
          return;
        }

        setError('Unable to load item details.');
      } finally {
        if (abortControllerRef.current === controller) {
          abortControllerRef.current = null;
          setLoading(false);
        }
      }
    };

    void fetchItemDetails();

    return () => {
      controller.abort();
    };
  }, [apiHandler, getUrl, itemId]);

  return { data, loading, error };
};
