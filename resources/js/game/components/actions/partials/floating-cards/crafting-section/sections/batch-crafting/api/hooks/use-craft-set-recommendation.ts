import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import CraftSetRecommendationDefinition from '../definitions/craft-set-recommendation-definition';
import { BatchCraftingApiUrls } from '../enums/batch-crafting-api-urls';
import UseCraftSetRecommendationDefinition from './definitions/use-craft-set-recommendation-definition';
import { extractBatchCraftingApiError } from '../../utils/extract-batch-crafting-api-error';

export const useCraftSetRecommendation = (
  characterId: number
): UseCraftSetRecommendationDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const [recommendation, setRecommendation] =
    useState<CraftSetRecommendationDefinition | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const abortControllerRef = useRef<AbortController | null>(null);
  const requestGenerationRef = useRef(0);

  const fetchRecommendation = useCallback(async () => {
    if (characterId <= 0) {
      return;
    }

    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;
    const requestGeneration = ++requestGenerationRef.current;

    setRecommendation(null);
    setLoading(true);
    setError(null);

    try {
      const result = await apiHandler.get<
        CraftSetRecommendationDefinition,
        never
      >(
        getUrl(BatchCraftingApiUrls.CRAFT_SET_RECOMMENDATION, {
          character: characterId,
        }),
        { signal: controller.signal }
      );

      if (requestGenerationRef.current !== requestGeneration) {
        return;
      }

      setRecommendation(result);
    } catch (requestError) {
      if (axios.isCancel(requestError)) {
        return;
      }

      if (requestGenerationRef.current !== requestGeneration) {
        return;
      }

      setError(
        extractBatchCraftingApiError(
          requestError,
          'Unable to recommend a Craft Set.'
        )
      );
    } finally {
      if (
        requestGenerationRef.current === requestGeneration &&
        abortControllerRef.current === controller
      ) {
        abortControllerRef.current = null;
        setLoading(false);
      }
    }
  }, [apiHandler, getUrl, characterId]);

  useEffect(() => {
    requestGenerationRef.current += 1;
    abortControllerRef.current?.abort();
    abortControllerRef.current = null;
    setRecommendation(null);

    void fetchRecommendation();

    return () => {
      abortControllerRef.current?.abort();
    };
  }, [fetchRecommendation]);

  return { recommendation, loading, error };
};
