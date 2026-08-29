import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';

import UseImportItemsDefinition from './definitions/use-import-items-definition';
import ItemImportResponseDefinition from '../definitions/item-import-response-definition';
import { ItemApiMessages } from '../enums/item-api-messages';
import { ItemApiUrls } from '../enums/item-api-urls';

export const useImportItems = (): UseImportItemsDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();
  const [importing, setImporting] = useState(false);
  const [error, setError] = useState<UseImportItemsDefinition['error']>(null);
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

  const importItems = async (file: File): Promise<boolean> => {
    if (importingRef.current) {
      return false;
    }

    importingRef.current = true;
    setImporting(true);
    setError(null);

    const formData = new FormData();
    const controller = new AbortController();

    formData.append('items_import', file);
    abortControllerRef.current = controller;

    try {
      await apiHandler.post<
        ItemImportResponseDefinition,
        Record<string, never>,
        FormData
      >(getUrl(ItemApiUrls.IMPORT), formData, {
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
            ItemApiMessages.ImportFallback,
        });

        return false;
      }

      setError({ message: ItemApiMessages.ImportFallback });

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
    import_items: importItems,
    importing,
    error,
  };
};
