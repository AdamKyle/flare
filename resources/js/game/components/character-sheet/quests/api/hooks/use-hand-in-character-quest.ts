import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError } from 'axios';
import { useState } from 'react';

import UseHandInCharacterQuestDefinition from './definitions/use-hand-in-character-quest-definition';
import UseHandInCharacterQuestParams from './definitions/use-hand-in-character-quest-params';
import CharacterQuestHandInResponseDefinition from '../definitions/character-quest-hand-in-response-definition';
import { CharacterQuestApiUrls } from '../enums/character-quest-api-urls';

const errorMessage = (error: unknown): string =>
  error instanceof AxiosError
    ? (error.response?.data?.message ?? 'Unable to hand in this Quest.')
    : 'Unable to hand in this Quest.';

export const useHandInCharacterQuest = ({
  characterId,
  questId,
}: UseHandInCharacterQuestParams): UseHandInCharacterQuestDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const [handingIn, setHandingIn] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);

  const handInQuest =
    async (): Promise<CharacterQuestHandInResponseDefinition | null> => {
      if (characterId <= 0 || questId <= 0) {
        return null;
      }

      setHandingIn(true);
      setError(null);
      setSuccessMessage(null);

      try {
        const response = await apiHandler.post<
          CharacterQuestHandInResponseDefinition,
          never,
          Record<string, never>
        >(
          getUrl(CharacterQuestApiUrls.HAND_IN, {
            character: characterId,
            quest: questId,
          }),
          {}
        );

        setSuccessMessage(response.message);

        return response;
      } catch (requestError) {
        setError(errorMessage(requestError));

        return null;
      } finally {
        setHandingIn(false);
      }
    };

  return { handingIn, error, successMessage, handInQuest };
};
