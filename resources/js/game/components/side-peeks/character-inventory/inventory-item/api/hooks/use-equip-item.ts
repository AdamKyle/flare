import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { resolveApiErrorMessage } from 'api-handler/utils/resolve-api-error-message';
import axios, { AxiosError, AxiosRequestConfig } from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import { GuideWalkthroughEvent } from '../../../../../guide-quests/walkthrough/enums/guide-walkthrough-event';
import { useGuideWalkthroughEventEmitter } from '../../../../../guide-quests/walkthrough/hooks/use-guide-walkthrough-event-emitter';
import { CharacterInventoryApiUrls } from '../../../api/enums/character-inventory-api-urls';
import UseEquipItemApiDefinition from '../definitions/use-equip-item-api-definition';
import UseEquipItemApiResponseDefinition from '../definitions/use-equip-item-api-response-definition';
import UseEquipItemParamsDefinition from '../definitions/use-equip-item-params-definition';
import UseEquipItemRequestParamsDefinition from '../definitions/use-equip-item-request-params-definition';

export const useEquipItem = ({
  character_id,
  on_success,
}: UseEquipItemParamsDefinition): UseEquipItemApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();
  const guideWalkthroughEventEmitter = useGuideWalkthroughEventEmitter();

  const [error, setError] = useState<UseEquipItemApiDefinition['error']>(null);
  const [loading, setLoading] = useState(false);
  const [requestParams, setRequestParams] =
    useState<UseEquipItemRequestParamsDefinition>({
      position: null,
      slot_id: 0,
      equip_type: null,
    });

  const abortControllerRef = useRef<AbortController | null>(null);
  const onSuccessRef = useRef(on_success);

  useEffect(() => {
    onSuccessRef.current = on_success;
  }, [on_success]);

  useEffect(() => {
    return () => {
      const activeController = abortControllerRef.current;

      abortControllerRef.current = null;
      activeController?.abort();
    };
  }, []);

  const equipSelectedItem = useCallback(async (): Promise<void> => {
    if (character_id <= 0 || requestParams.slot_id === 0) {
      return;
    }

    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    try {
      const result = await apiHandler.post<
        UseEquipItemApiResponseDefinition,
        AxiosRequestConfig<UseEquipItemRequestParamsDefinition>,
        UseEquipItemRequestParamsDefinition
      >(
        getUrl(CharacterInventoryApiUrls.CHARACTER_EQUIP_ITEM, {
          character: character_id,
        }),
        {
          slot_id: requestParams.slot_id,
          position: requestParams.position,
          equip_type: requestParams.equip_type,
        },
        { signal: controller.signal }
      );

      if (abortControllerRef.current !== controller) {
        return;
      }

      guideWalkthroughEventEmitter.emit(
        GuideWalkthroughEvent.ITEM_EQUIPPED,
        undefined
      );
      onSuccessRef.current(result.message);
    } catch (requestError) {
      if (axios.isCancel(requestError)) {
        return;
      }

      if (abortControllerRef.current !== controller) {
        return;
      }

      setError({
        message: resolveApiErrorMessage(
          requestError,
          'Unable to equip the item.'
        ),
      });

      if (requestError instanceof AxiosError) {
        handleInactivity({ response: requestError, setError });
      }
    } finally {
      if (abortControllerRef.current === controller) {
        abortControllerRef.current = null;
        setLoading(false);
      }
    }
  }, [
    apiHandler,
    character_id,
    getUrl,
    guideWalkthroughEventEmitter,
    handleInactivity,
    requestParams,
  ]);

  useEffect(() => {
    void equipSelectedItem();
  }, [equipSelectedItem]);

  const resetError = useCallback((): void => {
    setError(null);
  }, []);

  return {
    error,
    loading,
    resetError,
    setRequestParams,
  };
};
