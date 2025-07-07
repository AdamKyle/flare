import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig, AxiosResponse } from 'axios';
import { useCallback, useEffect, useState } from 'react';

import LocationDetailsApi from '../../api-definitions/location-details-api';
import LocationDetailsResponseDefinition from '../../api-definitions/location-details-response-definition';
import LocationDetailsParams from '../definitions/location-details-params';
import UseFetchLocationDetailsApiDefinition from '../definitions/use-fetch-location-details-api-definition';

export const useFetchLocationDetailsApi = (
  params: LocationDetailsParams
): UseFetchLocationDetailsApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const url = getUrl(params.url, { location: params.location_id });

  const [data, setData] = useState<LocationDetailsApi | null>(null);
  const [error, setError] =
    useState<UseFetchLocationDetailsApiDefinition['error']>(null);
  const [loading, setLoading] = useState(true);

  const fetchLocationDetails = useCallback(async () => {
    try {
      const result = await apiHandler.get<
        LocationDetailsResponseDefinition,
        AxiosRequestConfig<AxiosResponse<LocationDetailsResponseDefinition>>
      >(url);

      setData(result.data);
    } catch (err) {
      if (err instanceof AxiosError) {
        setError(err.response?.data.message || null);
      }
    } finally {
      setLoading(false);
    }
  }, [apiHandler, url]);

  useEffect(() => {
    fetchLocationDetails().catch(() => {});
  }, [fetchLocationDetails]);

  return {
    data,
    error,
    loading,
  };
};
