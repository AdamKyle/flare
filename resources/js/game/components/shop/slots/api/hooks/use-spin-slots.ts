import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { resolveApiErrorMessage } from 'api-handler/utils/resolve-api-error-message';
import axios, { AxiosError, AxiosRequestConfig } from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import UseSpinSlotsDefinition from './definitions/use-spin-slots-definition';
import UseSpinSlotsParams from './definitions/use-spin-slots-params';
import { isValidSlotRollSet } from '../../utils/is-valid-slot-roll-set';
import SpinSlotsResponseDefinition from '../definitions/spin-slots-response-definition';
import { SlotsApiUrls } from '../enums/slots-api-urls';

export const useSpinSlots = ({
  character_id,
  symbol_count,
}: UseSpinSlotsParams): UseSpinSlotsDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<UseSpinSlotsDefinition['error']>(null);

  const isSubmittingRef = useRef(false);
  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    return () => {
      const activeController = abortControllerRef.current;

      abortControllerRef.current = null;
      activeController?.abort();
    };
  }, []);

  const spin =
    useCallback(async (): Promise<SpinSlotsResponseDefinition | null> => {
      if (isSubmittingRef.current || character_id <= 0) {
        return null;
      }

      isSubmittingRef.current = true;
      const controller = new AbortController();
      abortControllerRef.current = controller;

      setLoading(true);
      setError(null);

      try {
        const result = await apiHandler.post<
          SpinSlotsResponseDefinition,
          AxiosRequestConfig<SpinSlotsResponseDefinition>,
          Record<string, never>
        >(
          getUrl(SlotsApiUrls.SPIN, { character: character_id }),
          {},
          { signal: controller.signal }
        );

        if (!isValidSlotRollSet(result.rolls, symbol_count)) {
          setError({ message: 'The slot machine returned an invalid result.' });

          return null;
        }

        return result;
      } catch (requestError) {
        if (axios.isCancel(requestError)) {
          return null;
        }

        setError({
          message: resolveApiErrorMessage(
            requestError,
            'Unable to spin the slot machine.'
          ),
        });

        if (requestError instanceof AxiosError) {
          handleInactivity({ response: requestError, setError });
        }

        return null;
      } finally {
        isSubmittingRef.current = false;

        if (abortControllerRef.current === controller) {
          abortControllerRef.current = null;
          setLoading(false);
        }
      }
    }, [apiHandler, getUrl, handleInactivity, character_id, symbol_count]);

  return { loading, error, spin };
};
