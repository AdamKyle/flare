import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import CraftAndEnchantSetPreviewDefinition from '../definitions/craft-and-enchant-set-preview-definition';
import CraftAndEnchantSetRequestDefinition from '../definitions/craft-and-enchant-set-request-definition';
import { BatchCraftingApiUrls } from '../enums/batch-crafting-api-urls';
import UseCraftAndEnchantSetPreviewDefinition from './definitions/use-craft-and-enchant-set-preview-definition';
import { extractBatchCraftingApiError } from '../../utils/extract-batch-crafting-api-error';

export const useCraftAndEnchantSetPreview = (
  characterId: number
): UseCraftAndEnchantSetPreviewDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const [preview, setPreview] =
    useState<CraftAndEnchantSetPreviewDefinition | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const abortControllerRef = useRef<AbortController | null>(null);
  const requestGenerationRef = useRef(0);

  useEffect(() => {
    return () => {
      abortControllerRef.current?.abort();
    };
  }, []);

  const fetchPreview = useCallback(
    async (request: CraftAndEnchantSetRequestDefinition) => {
      abortControllerRef.current?.abort();
      const controller = new AbortController();
      abortControllerRef.current = controller;
      const requestGeneration = ++requestGenerationRef.current;

      setPreview(null);
      setLoading(true);
      setError(null);

      try {
        const result = await apiHandler.post<
          CraftAndEnchantSetPreviewDefinition,
          never,
          CraftAndEnchantSetRequestDefinition
        >(
          getUrl(BatchCraftingApiUrls.PREVIEW, { character: characterId }),
          request,
          { signal: controller.signal }
        );

        if (requestGenerationRef.current !== requestGeneration) {
          return;
        }

        setPreview(result);
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
            'Unable to preview this set.'
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
    },
    [apiHandler, getUrl, characterId]
  );

  const clearPreview = useCallback(() => {
    abortControllerRef.current?.abort();
    requestGenerationRef.current += 1;
    abortControllerRef.current = null;
    setPreview(null);
    setError(null);
    setLoading(false);
  }, []);

  return { preview, loading, error, fetchPreview, clearPreview };
};
