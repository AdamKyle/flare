import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { resolveApiErrorMessage } from 'api-handler/utils/resolve-api-error-message';
import axios from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import UseCharacterSkillsDefinition from './definitions/use-character-skills-definition';
import UseCharacterSkillsParams from './definitions/use-character-skills-params';
import CharacterSkillDefinition from '../definitions/character-skill-definition';
import CharacterSkillsResponseDefinition from '../definitions/character-skills-response-definition';
import CharacterSkillsUpdateEventDefinition from '../definitions/character-skills-update-event-definition';
import { SkillsApiUrls } from '../enums/skills-api-urls';
import { SkillsWebSocketChannels } from '../enums/skills-web-socket-channels';
import { SkillsWebSocketEventNames } from '../enums/skills-web-socket-event-names';

import { ChannelType } from 'websockets/enums/channel-type';
import { useWebsocket } from 'websockets/hooks/use-websocket';

/**
 * `UpdateCharacterSkills` carries either the training list or the crafting
 * list per broadcast; an empty list means that category was not sent.
 */
export const useCharacterSkills = ({
  character_id: characterId,
  user_id: userId,
}: UseCharacterSkillsParams): UseCharacterSkillsDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [trainingSkills, setTrainingSkills] = useState<
    CharacterSkillDefinition[]
  >([]);
  const [craftingSkills, setCraftingSkills] = useState<
    CharacterSkillDefinition[]
  >([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    if (characterId <= 0) {
      return;
    }

    const controller = new AbortController();
    abortControllerRef.current = controller;

    const fetchSkills = async (): Promise<void> => {
      setLoading(true);
      setError(null);

      try {
        const result = await apiHandler.get<
          CharacterSkillsResponseDefinition,
          never
        >(getUrl(SkillsApiUrls.SKILLS, { character: characterId }), {
          signal: controller.signal,
        });

        setTrainingSkills(result.training_skills);
        setCraftingSkills(result.crafting_skills);
      } catch (requestError) {
        if (axios.isCancel(requestError)) {
          return;
        }

        setError(
          resolveApiErrorMessage(requestError, 'Unable to load your Skills.')
        );
      } finally {
        if (abortControllerRef.current === controller) {
          abortControllerRef.current = null;
          setLoading(false);
        }
      }
    };

    void fetchSkills();

    return () => {
      controller.abort();
    };
  }, [apiHandler, getUrl, characterId]);

  const handleSkillsUpdated = useCallback(
    (event: CharacterSkillsUpdateEventDefinition) => {
      if (event.trainingSkills.length > 0) {
        setTrainingSkills(event.trainingSkills);
      }

      if (event.craftingSkills.length > 0) {
        setCraftingSkills(event.craftingSkills);
      }
    },
    []
  );

  useWebsocket<CharacterSkillsUpdateEventDefinition>({
    url: SkillsWebSocketChannels.UPDATE_SKILLS,
    params: { userId },
    type: ChannelType.PRIVATE,
    channelName: SkillsWebSocketEventNames.UPDATE_SKILLS,
    onEvent: handleSkillsUpdated,
    enabled: userId > 0,
  });

  return {
    training_skills: trainingSkills,
    crafting_skills: craftingSkills,
    loading,
    error,
    replace_training_skills: setTrainingSkills,
  };
};
