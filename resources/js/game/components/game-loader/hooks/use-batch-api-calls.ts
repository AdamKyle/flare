import { AxiosError } from 'axios';
import { useCallback, useRef, useState } from 'react';

import BatchApiCallsParameterDefinition from './definitions/batch-api-calls-parameter-definition';
import UseBatchApiCallsDefinition from './definitions/use-batch-api-calls-definition';
import { BatchApiCallKey } from './enums/batch-api-call-key';

import GameDataDefinition from 'game-data/deffinitions/game-data-definition';

export const useBatchApiCalls = (
  apiCalls: BatchApiCallsParameterDefinition[]
): UseBatchApiCallsDefinition => {
  const [loading, setLoading] = useState<boolean>(true);
  const [progress, setProgress] = useState<number>(0);
  const [error, setError] = useState<UseBatchApiCallsDefinition['error']>(null);
  const [data, setData] = useState<GameDataDefinition>({
    character: null,
    monsters: [],
    announcements: [],
    hasNewAnnouncements: false,
  });

  const hasExecutedRef = useRef(false);

  const executeBatchApiCalls = useCallback(async () => {
    if (hasExecutedRef.current) {
      return;
    }

    hasExecutedRef.current = true;

    for (const { api_call, progress_step, key } of apiCalls) {
      try {
        const responseData = await api_call();

        setData((prevData) => {
          const nextData: GameDataDefinition = {
            ...prevData,
            [key]: responseData,
          };

          if (
            key === BatchApiCallKey.ANNOUNCEMENTS &&
            Array.isArray(responseData) &&
            responseData.length > 0
          ) {
            nextData.hasNewAnnouncements = true;
          }

          return nextData;
        });

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
