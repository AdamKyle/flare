import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseImportClassesDefinition from './definitions/use-import-classes-definition';
import { ClassApiMessages } from '../enums/class-api-messages';
import { ClassApiUrls } from '../enums/class-api-urls';

export const useImportClasses = (): UseImportClassesDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();
  const [importing, setImporting] = useState(false);
  const [error, setError] = useState<AxiosErrorDefinition | null>(null);
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

  const importClasses = async (file: File): Promise<boolean> => {
    if (importingRef.current) {
      return false;
    }

    importingRef.current = true;
    setImporting(true);
    setError(null);

    const formData = new FormData();
    const controller = new AbortController();

    formData.append('classes_import', file);
    abortControllerRef.current = controller;

    try {
      await apiHandler.post<
        { message: string },
        Record<string, never>,
        FormData
      >(getUrl(ClassApiUrls.IMPORT), formData, {
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
            ClassApiMessages.ImportFallback,
        });

        return false;
      }

      setError({ message: ClassApiMessages.ImportFallback });

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
    import_classes: importClasses,
    importing,
    error,
  };
};
