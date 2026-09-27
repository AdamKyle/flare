import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseImportPassiveSkillsDefinition from './definitions/use-import-passive-skills-definition';
import { PassiveSkillApiMessages } from '../enums/passive-skill-api-messages';
import { PassiveSkillApiUrls } from '../enums/passive-skill-api-urls';

export const useImportPassiveSkills = (): UseImportPassiveSkillsDefinition => {
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

  const importPassiveSkills = async (file: File): Promise<boolean> => {
    if (importingRef.current) {
      return false;
    }

    importingRef.current = true;
    setImporting(true);
    setError(null);

    const formData = new FormData();
    const controller = new AbortController();

    formData.append('passives_import', file);
    abortControllerRef.current = controller;

    try {
      await apiHandler.post<
        { message: string },
        Record<string, never>,
        FormData
      >(getUrl(PassiveSkillApiUrls.IMPORT), formData, {
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
            PassiveSkillApiMessages.ImportFallback,
        });

        return false;
      }

      setError({ message: PassiveSkillApiMessages.ImportFallback });

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
    import_passive_skills: importPassiveSkills,
    importing,
    error,
  };
};
