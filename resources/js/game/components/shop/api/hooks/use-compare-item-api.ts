import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig, AxiosResponse } from 'axios';
import { useCallback, useEffect, useState } from 'react';

import UseCompareItemApiDefinition from './definitions/use-compare-item-api-definition';
import UseCompareItemApiRequestParameters from './definitions/use-compare-item-api-request-params';
import { UseCompareItemApiResponseDefinition } from './definitions/use-compare-item-api-response-definition';
import { ItemComparison } from '../../../../api-definitions/items/item-comparison-details';

export const useCompareItemApi = (
  params: UseCompareItemApiRequestParameters
): UseCompareItemApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [loading, setLoading] = useState(true);
  const [data, setData] = useState<ItemComparison | null>(null);
  const [error, setError] =
    useState<UseCompareItemApiDefinition['error']>(null);

  // Depend on the character id (a stable primitive) rather than the whole
  // `characterData` object, which is replaced on every unrelated character
  // update and would otherwise re-trigger this fetch constantly.
  const characterId = params.characterData?.id;

  let url = '';

  if (characterId) {
    url = getUrl(params.url, { character: characterId });
  }

  const fetchComparisonData = useCallback(async () => {
    if (!characterId) {
      setLoading(false);
    }

    try {
      const result = await apiHandler.get<
        UseCompareItemApiResponseDefinition,
        AxiosRequestConfig<AxiosResponse<UseCompareItemApiResponseDefinition>>
      >(url, {
        params: {
          item_type: params.item_type,
          item_name: params.item_name,
        },
      });

      setData(result);
    } catch (err) {
      if (err instanceof AxiosError) {
        setError(err.response?.data || null);
      }
    } finally {
      setLoading(false);
    }
  }, [apiHandler, url, characterId, params.item_type, params.item_name]);

  useEffect(() => {
    fetchComparisonData().catch(() => {});
  }, [fetchComparisonData]);

  return {
    loading,
    data,
    error,
  };
};
