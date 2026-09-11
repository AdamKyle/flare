import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios, { AxiosRequestConfig } from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import UseAcknowledgeGemWorldIntroductionDefinition from './definitions/use-acknowledge-gem-world-introduction-definition';
import AcknowledgeGemWorldIntroductionResponseDefinition from '../definitions/acknowledge-gem-world-introduction-response-definition';
import { GemProgressionApiUrls } from '../enums/gem-progression-api-urls';

import { useGameData } from 'game-data/hooks/use-game-data';

export const useAcknowledgeGemWorldIntroduction = (
  characterId: number
): UseAcknowledgeGemWorldIntroductionDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { updateCharacter } = useGameData();

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const abortControllerRef = useRef<AbortController | null>(null);
  const isSubmittingRef = useRef(false);

  useEffect(() => {
    return () => {
      abortControllerRef.current?.abort();
    };
  }, []);

  const acknowledge = useCallback(async (): Promise<boolean> => {
    if (isSubmittingRef.current || characterId <= 0) {
      return false;
    }

    isSubmittingRef.current = true;
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    try {
      const response = await apiHandler.post<
        AcknowledgeGemWorldIntroductionResponseDefinition,
        AxiosRequestConfig<AcknowledgeGemWorldIntroductionResponseDefinition>,
        Record<string, never>
      >(
        getUrl(GemProgressionApiUrls.ACKNOWLEDGE_INTRODUCTION, {
          character: characterId,
        }),
        {},
        { signal: controller.signal }
      );

      updateCharacter({
        gem_world_introduction_acknowledged_at:
          response.gem_world_introduction_acknowledged_at,
      });

      return true;
    } catch (requestError) {
      if (axios.isCancel(requestError)) {
        return false;
      }

      setError('Unable to acknowledge the Gem World introduction.');

      return false;
    } finally {
      isSubmittingRef.current = false;
      abortControllerRef.current = null;
      setLoading(false);
    }
  }, [apiHandler, getUrl, characterId, updateCharacter]);

  return { loading, error, acknowledge };
};
