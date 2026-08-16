import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import BatchCraftingPreviewDefinition from '../definitions/batch-crafting-preview-definition';
import CraftAmountRequestDefinition from '../definitions/craft-amount-request-definition';
import { BatchCraftingApiUrls } from '../enums/batch-crafting-api-urls';
import UseBatchCraftingPreviewDefinition from './definitions/use-batch-crafting-preview-definition';
import { extractBatchCraftingApiError } from '../../utils/extract-batch-crafting-api-error';

export const useBatchCraftingPreview = (
  characterId: number
): UseBatchCraftingPreviewDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const [preview, setPreview] = useState<BatchCraftingPreviewDefinition | null>(
    null
  );
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const abortControllerRef = useRef<AbortController | null>(null);
  const requestGenerationRef = useRef(0);

  useEffect(() => {
    return () => {
      abortControllerRef.current?.abort();
    };
  }, []);

  const fetchPreview = async (request: CraftAmountRequestDefinition) => {
    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;
    const requestGeneration = ++requestGenerationRef.current;

    setPreview(null);
    setLoading(true);
    setError(null);

    try {
      const result = await apiHandler.post<
        BatchCraftingPreviewDefinition,
        never,
        CraftAmountRequestDefinition
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
          'Unable to preview this batch.'
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
  };

  const clearPreview = () => {
    abortControllerRef.current?.abort();
    requestGenerationRef.current += 1;
    abortControllerRef.current = null;
    setPreview(null);
    setError(null);
    setLoading(false);
  };

  return { preview, loading, error, fetchPreview, clearPreview };
};
