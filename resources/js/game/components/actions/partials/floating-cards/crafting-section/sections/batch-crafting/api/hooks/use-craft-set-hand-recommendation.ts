import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import CraftSetHandRecommendationDefinition from '../definitions/craft-set-hand-recommendation-definition';
import CraftSetHandRecommendationItemDefinition from '../definitions/craft-set-hand-recommendation-item-definition';
import { BatchCraftingApiUrls } from '../enums/batch-crafting-api-urls';
import UseCraftSetHandRecommendationDefinition from './definitions/use-craft-set-hand-recommendation-definition';
import UseCraftSetHandRecommendationParams from './definitions/use-craft-set-hand-recommendation-params';
import { extractBatchCraftingApiError } from '../../utils/extract-batch-crafting-api-error';

export const useCraftSetHandRecommendation = ({
  characterId,
  handType,
}: UseCraftSetHandRecommendationParams): UseCraftSetHandRecommendationDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const [recommendation, setRecommendation] =
    useState<CraftSetHandRecommendationItemDefinition | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const abortControllerRef = useRef<AbortController | null>(null);
  const requestGenerationRef = useRef(0);

  useEffect(() => {
    requestGenerationRef.current += 1;
    const requestGeneration = requestGenerationRef.current;

    abortControllerRef.current?.abort();
    abortControllerRef.current = null;
    setRecommendation(null);
    setError(null);

    if (characterId <= 0 || !handType) {
      setLoading(false);

      return;
    }

    const controller = new AbortController();
    abortControllerRef.current = controller;
    setLoading(true);

    const fetchRecommendation = async () => {
      try {
        const result = await apiHandler.get<
          CraftSetHandRecommendationDefinition,
          { hand_type: string }
        >(
          getUrl(BatchCraftingApiUrls.CRAFT_SET_HAND_RECOMMENDATION, {
            character: characterId,
          }),
          { params: { hand_type: handType }, signal: controller.signal }
        );

        if (requestGenerationRef.current !== requestGeneration) {
          return;
        }

        setRecommendation(result.item);
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
            'Unable to recommend a hand item.'
          )
        );
      } finally {
        if (requestGenerationRef.current === requestGeneration) {
          abortControllerRef.current = null;
          setLoading(false);
        }
      }
    };

    void fetchRecommendation();

    return () => {
      controller.abort();
    };
  }, [apiHandler, getUrl, characterId, handType]);

  return { recommendation, loading, error };
};
