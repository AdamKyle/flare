import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError } from 'axios';
import { useCallback, useEffect, useState } from 'react';

import SeerCampApiResponseDefinition from '../definitions/seer-camp-api-response-definition';
import { SeerCampApiUrls } from '../enums/seer-camp-api-urls';
import UseSeerCampApiDefinition from './definitions/use-seer-camp-api-definition';
import UseSeerCampApiParams from './definitions/use-seer-camp-api-params';

const getSeerCampErrorMessage = (
  requestError: unknown,
  fallbackMessage: string
): string =>
  requestError instanceof AxiosError
    ? (requestError.response?.data?.message ?? fallbackMessage)
    : fallbackMessage;

export const useSeerCampApi = ({
  characterId,
}: UseSeerCampApiParams): UseSeerCampApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [data, setData] = useState<SeerCampApiResponseDefinition | null>(null);
  const [loading, setLoading] = useState(characterId > 0);
  const [error, setError] = useState<string | null>(null);
  const [removalLoading, setRemovalLoading] = useState(false);
  const [removalError, setRemovalError] = useState<string | null>(null);

  const fetchSeerCampData = useCallback(async (): Promise<void> => {
    setLoading(true);
    setError(null);

    try {
      setData(
        await apiHandler.get<SeerCampApiResponseDefinition, never>(
          getUrl(SeerCampApiUrls.FETCH, { character: characterId })
        )
      );
    } catch (requestError) {
      setError(
        getSeerCampErrorMessage(requestError, 'Unable to visit Seer Camp.')
      );
    } finally {
      setLoading(false);
    }
  }, [apiHandler, characterId, getUrl]);

  const fetchRemovalData = useCallback(async (): Promise<void> => {
    setRemovalLoading(true);
    setRemovalError(null);

    try {
      setData(
        await apiHandler.get<SeerCampApiResponseDefinition, never>(
          getUrl(SeerCampApiUrls.FETCH_GEMS_TO_REMOVE, {
            character: characterId,
          })
        )
      );
    } catch (requestError) {
      setRemovalError(
        getSeerCampErrorMessage(
          requestError,
          'Unable to load Gem removal data.'
        )
      );
    } finally {
      setRemovalLoading(false);
    }
  }, [apiHandler, characterId, getUrl]);

  useEffect(() => {
    if (characterId === 0) {
      setLoading(false);
      return;
    }

    void fetchSeerCampData();
  }, [characterId, fetchSeerCampData]);

  return {
    data,
    loading,
    error,
    removalLoading,
    removalError,
    replaceData: setData,
    fetchRemovalData,
  };
};
