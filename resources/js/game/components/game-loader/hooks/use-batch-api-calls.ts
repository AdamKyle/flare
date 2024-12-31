import { AxiosError } from 'axios';
import { useCallback, useRef, useState } from 'react';

import BatchApiCallsParameterDefinition from './definitions/batch-api-calls-parameter-definition';
import UseBatchApiCallsDefinition from './definitions/use-batch-api-calls-definition';
import { BatchApiCallsState } from './types/use-batch-api-calls-state';

export const useBatchApiCalls = (
  apiCalls: BatchApiCallsParameterDefinition[]
): UseBatchApiCallsDefinition => {
  const [loading, setLoading] = useState<BatchApiCallsState['loading']>(true);
  const [progress, setProgress] = useState<BatchApiCallsState['progress']>(0);
  const [error, setError] = useState<BatchApiCallsState['error']>(null);
  const [data, setData] = useState<BatchApiCallsState['data']>({
    character: null,
    monsters: [],
  });

  const hasExecutedRef = useRef(false);

  const executeBatchApiCalls = useCallback(async () => {
    if (hasExecutedRef.current) return;

    hasExecutedRef.current = true;

    for (const { api_call, progress_step, key } of apiCalls) {
      try {
        const responseData = await api_call();

        setData((prevData) => ({
          ...prevData,
          [key]: responseData,
        }));

        setProgress((prevState) => prevState + progress_step);
      } catch (error) {
        if (error instanceof AxiosError) {
          setError(
            error.response?.data || {
              message:
                'Something went horribly wrong child. Call the creator! Go to discord! (Top right profile icon -> discord. Post this in #bugs)',
            }
          );
        } else {
          setError({
            message:
              'Something went horribly wrong child. Call the creator! Go to discord! (Top right profile icon -> discord. Post this in #bugs)',
          });
        }
      }
    }

    setLoading(false);
  }, [apiCalls]);

  return {
    loading,
    progress,
    error,
    data,
    executeBatchApiCalls,
  };
};
