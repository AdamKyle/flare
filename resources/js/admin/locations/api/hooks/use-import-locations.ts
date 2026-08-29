import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';

import UseImportLocationsDefinition from './definitions/use-import-locations-definition';
import LocationImportResponseDefinition from '../definitions/location-import-response-definition';
import { LocationApiMessages } from '../enums/location-api-messages';
import { LocationApiUrls } from '../enums/location-api-urls';

export const useImportLocations = (): UseImportLocationsDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();
  const [importing, setImporting] = useState(false);
  const [error, setError] =
    useState<UseImportLocationsDefinition['error']>(null);
  const importingRef = useRef(false);
  const isMountedRef = useRef(true);
  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    isMountedRef.current = true;

    return () => {
      isMountedRef.current = false;
      abortControllerRef.current?.abort();
    };
  }, []);

  const importLocations = async (file: File): Promise<boolean> => {
    if (importingRef.current) {
      return false;
    }

    importingRef.current = true;
    setImporting(true);
    setError(null);

    const formData = new FormData();
    const controller = new AbortController();

    formData.append('locations_import', file);
    abortControllerRef.current = controller;

    try {
      await apiHandler.post<
        LocationImportResponseDefinition,
        Record<string, never>,
        FormData
      >(getUrl(LocationApiUrls.IMPORT), formData, {
        signal: controller.signal,
      });

      return true;
    } catch (errorInstance) {
      if (axios.isCancel(errorInstance) || !isMountedRef.current) {
        return false;
      }

      if (axios.isAxiosError<{ message?: string }>(errorInstance)) {
        if (errorInstance.response?.status === 401) {
          handleInactivity({ setError, response: errorInstance });

          return false;
        }

        setError({
          message:
            errorInstance.response?.data?.message ??
            LocationApiMessages.ImportFallback,
        });

        return false;
      }

      setError({ message: LocationApiMessages.ImportFallback });

      return false;
    } finally {
      importingRef.current = false;

      if (isMountedRef.current) {
        abortControllerRef.current = null;
        setImporting(false);
      }
    }
  };

  return {
    import_locations: importLocations,
    importing,
    error,
  };
};
