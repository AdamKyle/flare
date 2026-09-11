import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError } from 'axios';
import { useState } from 'react';

import UseGemScrollActionsDefinition from './definitions/use-gem-scroll-actions-definition';
import UseGemScrollActionsParams from './definitions/use-gem-scroll-actions-params';
import GemScrollActionResponseDefinition from '../definitions/gem-scroll-action-response-definition';
import { GemScrollManagementApiUrls } from '../enums/gem-scroll-management-api-urls';

const errorMessage = (error: unknown, fallback: string): string =>
  error instanceof AxiosError
    ? (error.response?.data?.message ?? fallback)
    : fallback;

export const useGemScrollActions = ({
  characterId,
  onSuccess,
}: UseGemScrollActionsParams): UseGemScrollActionsDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [actingScrollId, setActingScrollId] = useState<number | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const [mutationError, setMutationError] = useState<string | null>(null);

  const runAction = async (
    scrollId: number,
    url: string,
    fallbackErrorMessage: string
  ): Promise<void> => {
    if (characterId <= 0) {
      return;
    }

    setActingScrollId(scrollId);
    setSuccessMessage(null);
    setMutationError(null);

    try {
      const response = await apiHandler.post<
        GemScrollActionResponseDefinition,
        never,
        Record<string, never>
      >(url, {});

      setSuccessMessage(response.message);
      onSuccess();
    } catch (requestError) {
      setMutationError(errorMessage(requestError, fallbackErrorMessage));
    } finally {
      setActingScrollId(null);
    }
  };

  const useScroll = async (alchemyBagSlotId: number): Promise<void> => {
    await runAction(
      alchemyBagSlotId,
      getUrl(GemScrollManagementApiUrls.USE_SCROLL, {
        character: characterId,
        alchemyBagSlot: alchemyBagSlotId,
      }),
      'Unable to activate this Gem Scroll.'
    );
  };

  const fillMapScroll = async (
    characterGameMapGemScrollId: number,
    alchemyBagSlotId: number
  ): Promise<void> => {
    await runAction(
      characterGameMapGemScrollId,
      getUrl(GemScrollManagementApiUrls.FILL_MAP_SCROLL, {
        character: characterId,
        characterGameMapGemScroll: characterGameMapGemScrollId,
        alchemyBagSlot: alchemyBagSlotId,
      }),
      'Unable to fill up this Gem Scroll.'
    );
  };

  const fillLocationScroll = async (
    characterGameLocationGemScrollId: number,
    alchemyBagSlotId: number
  ): Promise<void> => {
    await runAction(
      characterGameLocationGemScrollId,
      getUrl(GemScrollManagementApiUrls.FILL_LOCATION_SCROLL, {
        character: characterId,
        characterGameLocationGemScroll: characterGameLocationGemScrollId,
        alchemyBagSlot: alchemyBagSlotId,
      }),
      'Unable to fill up this Gem Scroll.'
    );
  };

  const removeMapScroll = async (
    characterGameMapGemScrollId: number
  ): Promise<void> => {
    await runAction(
      characterGameMapGemScrollId,
      getUrl(GemScrollManagementApiUrls.REMOVE_MAP_SCROLL, {
        character: characterId,
        characterGameMapGemScroll: characterGameMapGemScrollId,
      }),
      'Unable to remove this Gem Scroll.'
    );
  };

  const removeLocationScroll = async (
    characterGameLocationGemScrollId: number
  ): Promise<void> => {
    await runAction(
      characterGameLocationGemScrollId,
      getUrl(GemScrollManagementApiUrls.REMOVE_LOCATION_SCROLL, {
        character: characterId,
        characterGameLocationGemScroll: characterGameLocationGemScrollId,
      }),
      'Unable to remove this Gem Scroll.'
    );
  };

  return {
    actingScrollId,
    successMessage,
    mutationError,
    useScroll,
    fillMapScroll,
    fillLocationScroll,
    removeMapScroll,
    removeLocationScroll,
  };
};
