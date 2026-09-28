import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseGemAbilityForEditDefinition from './definitions/use-gem-ability-for-edit-definition';
import GemAbilityFormDefinition from '../definitions/gem-ability-form-definition';
import { GemAbilityApiMessages } from '../enums/gem-ability-api-messages';
import { GemAbilityApiUrls } from '../enums/gem-ability-api-urls';

export const useGemAbilityForEdit = (
  gemAbilityId: number | null
): UseGemAbilityForEditDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [gemAbility, setGemAbility] = useState<GemAbilityFormDefinition | null>(
    null
  );
  const [loading, setLoading] = useState(gemAbilityId !== null);
  const [error, setError] = useState<AxiosErrorDefinition | null>(null);

  const requestGenerationRef = useRef(0);
  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    if (gemAbilityId === null) {
      setLoading(false);

      return;
    }

    requestGenerationRef.current += 1;
    const requestGeneration = requestGenerationRef.current;

    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    const fetchGemAbility = async () => {
      try {
        const result = await apiHandler.get<
          GemAbilityFormDefinition,
          Record<string, never>
        >(getUrl(GemAbilityApiUrls.EDIT, { gameGemAbility: gemAbilityId }), {
          signal: controller.signal,
        });

        if (requestGenerationRef.current !== requestGeneration) {
          return;
        }

        setGemAbility(result);
      } catch (errorInstance) {
        if (axios.isCancel(errorInstance)) {
          return;
        }

        if (requestGenerationRef.current !== requestGeneration) {
          return;
        }

        if (axios.isAxiosError<{ message?: string }>(errorInstance)) {
          setError({
            message:
              errorInstance.response?.data?.message ?? errorInstance.message,
          });

          return;
        }

        setError({ message: GemAbilityApiMessages.Load });
      } finally {
        if (requestGenerationRef.current === requestGeneration) {
          setLoading(false);
        }
      }
    };

    void fetchGemAbility();

    return () => {
      controller.abort();
    };
  }, [apiHandler, getUrl, gemAbilityId]);

  return { gem_ability: gemAbility, loading, error };
};
