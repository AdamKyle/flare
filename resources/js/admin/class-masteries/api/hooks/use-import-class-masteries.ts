import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseImportClassMasteriesDefinition from './definitions/use-import-class-masteries-definition';
import { ClassMasteryApiMessages } from '../enums/class-mastery-api-messages';
import { ClassMasteryApiUrls } from '../enums/class-mastery-api-urls';

export const useImportClassMasteries =
  (): UseImportClassMasteriesDefinition => {
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

    const importClassMasteries = async (file: File): Promise<boolean> => {
      if (importingRef.current) {
        return false;
      }

      importingRef.current = true;
      setImporting(true);
      setError(null);

      const formData = new FormData();
      const controller = new AbortController();

      formData.append('class_masteries_import', file);
      abortControllerRef.current = controller;

      try {
        await apiHandler.post<
          { message: string },
          Record<string, never>,
          FormData
        >(getUrl(ClassMasteryApiUrls.IMPORT), formData, {
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
              ClassMasteryApiMessages.ImportFallback,
          });

          return false;
        }

        setError({ message: ClassMasteryApiMessages.ImportFallback });

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
      import_class_masteries: importClassMasteries,
      importing,
      error,
    };
  };
