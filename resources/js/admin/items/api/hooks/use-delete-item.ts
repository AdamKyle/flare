import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import { useApiHandler } from 'api-handler/hooks/use-api-handler';

import {
  DeleteItemResultDefinition,
  default as UseDeleteItemDefinition,
} from './definitions/use-delete-item-definition';
import { ItemApiMessages } from '../enums/item-api-messages';
import { ItemApiUrls } from '../enums/item-api-urls';

export const useDeleteItem = (): UseDeleteItemDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [deleting, setDeleting] = useState(false);
  const [error, setError] = useState<UseDeleteItemDefinition['error']>(null);
  const [blockers, setBlockers] = useState<string[]>([]);

  const deletingRef = useRef(false);
  const isMountedRef = useRef(true);
  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    isMountedRef.current = true;

    return () => {
      isMountedRef.current = false;
      abortControllerRef.current?.abort();
    };
  }, []);

  const deleteItem = async (itemId: number): Promise<boolean> => {
    if (deletingRef.current) {
      return false;
    }

    deletingRef.current = true;
    setDeleting(true);
    setError(null);
    setBlockers([]);

    const controller = new AbortController();
    abortControllerRef.current = controller;

    try {
      await apiHandler.delete<
        DeleteItemResultDefinition,
        Record<string, never>
      >(getUrl(ItemApiUrls.SHOW, { item: itemId }), {
        signal: controller.signal,
      });

      return true;
    } catch (errorInstance) {
      if (axios.isCancel(errorInstance)) {
        return false;
      }

      if (!isMountedRef.current) {
        return false;
      }

      if (axios.isAxiosError<DeleteItemResultDefinition>(errorInstance)) {
        const responseData = errorInstance.response?.data;

        setBlockers(responseData?.blockers ?? []);
        setError({
          message: responseData?.message ?? ItemApiMessages.DeleteFallback,
        });

        return false;
      }

      setError({ message: ItemApiMessages.DeleteFallback });

      return false;
    } finally {
      deletingRef.current = false;

      if (isMountedRef.current) {
        setDeleting(false);
      }
    }
  };

  return {
    deleting,
    error,
    blockers,
    delete_item: deleteItem,
  };
};
