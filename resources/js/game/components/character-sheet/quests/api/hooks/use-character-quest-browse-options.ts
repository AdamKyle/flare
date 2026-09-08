import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseCharacterQuestBrowseOptionsDefinition from './definitions/use-character-quest-browse-options-definition';
import UseCharacterQuestBrowseOptionsParams from './definitions/use-character-quest-browse-options-params';
import QuestBrowseOptionsDefinition from '../../../../../reusable-components/quest/api/definitions/quest-browse-options-definition';
import { CharacterQuestApiUrls } from '../enums/character-quest-api-urls';

export const useCharacterQuestBrowseOptions = ({
  characterId,
}: UseCharacterQuestBrowseOptionsParams): UseCharacterQuestBrowseOptionsDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [options, setOptions] = useState<QuestBrowseOptionsDefinition | null>(
    null
  );
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<AxiosErrorDefinition | null>(null);

  const requestGenerationRef = useRef(0);
  const abortControllerRef = useRef<AbortController | null>(null);
  const [refreshToken, setRefreshToken] = useState(0);

  useEffect(() => {
    requestGenerationRef.current += 1;
    const requestGeneration = requestGenerationRef.current;

    abortControllerRef.current?.abort();

    if (characterId <= 0) {
      abortControllerRef.current = null;
      setOptions(null);
      setLoading(false);
      setError(null);

      return;
    }

    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    const fetchOptions = async () => {
      try {
        const result = await apiHandler.get<
          QuestBrowseOptionsDefinition,
          Record<string, never>
        >(
          getUrl(CharacterQuestApiUrls.BROWSE_OPTIONS, {
            character: characterId,
          }),
          { signal: controller.signal }
        );

        if (requestGenerationRef.current !== requestGeneration) {
          return;
        }

        setOptions(result);
      } catch (errorInstance) {
        if (axios.isCancel(errorInstance)) {
          return;
        }

        if (requestGenerationRef.current !== requestGeneration) {
          return;
        }

        if (axios.isAxiosError<{ message?: string }>(errorInstance)) {
          setError({
            message:
              errorInstance.response?.data?.message ?? errorInstance.message,
          });

          return;
        }

        setError({ message: 'Unable to load the Quest browse options.' });
      } finally {
        if (requestGenerationRef.current === requestGeneration) {
          setLoading(false);
        }
      }
    };

    void fetchOptions();

    return () => {
      controller.abort();
    };
  }, [apiHandler, getUrl, characterId, refreshToken]);

  const refresh = (): void => {
    setRefreshToken((token) => token + 1);
  };

  return { options, loading, error, refresh };
};
