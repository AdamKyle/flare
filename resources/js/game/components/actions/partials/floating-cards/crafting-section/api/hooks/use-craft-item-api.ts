import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError } from 'axios';
import { useState } from 'react';

import { normalizeCraftingType } from '../../utils/normalize-crafting-type';
import CraftItemRequestDefinition from '../definitions/craft-item-request-definition';
import CraftingApiResponseDefinition from '../definitions/crafting-api-response-definition';
import { CraftingApiUrls } from '../enums/crafting-api-urls';
import UseCraftItemApiDefinition from './definitions/use-craft-item-api-definition';
import UseCraftItemApiParams from './definitions/use-craft-item-api-params';

const ITEMS_PER_PAGE = 10;

const getErrorMessage = (error: unknown): string => {
  if (error instanceof AxiosError) {
    return error.response?.data?.message ?? 'Unable to complete the request.';
  }

  return 'Unable to complete the request.';
};

export const useCraftItemApi = ({
  characterId,
  selectedItem,
}: UseCraftItemApiParams): UseCraftItemApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const [isCrafting, setIsCrafting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const [craftingResponse, setCraftingResponse] =
    useState<CraftingApiResponseDefinition | null>(null);

  const craftItem = async (craftForNpc: boolean, craftForEvent: boolean) => {
    if (!selectedItem) {
      return;
    }

    setIsCrafting(true);
    setError(null);
    setSuccessMessage(null);
    setCraftingResponse(null);

    const url = getUrl(CraftingApiUrls.CRAFT_ITEM, { character: characterId });

    const request: CraftItemRequestDefinition = {
      item_to_craft: selectedItem.id,
      type: normalizeCraftingType(selectedItem),
      craft_for_npc: craftForNpc,
      craft_for_event: craftForEvent,
      per_page: ITEMS_PER_PAGE,
      page: 1,
      search_text: '',
      filters: {},
    };

    try {
      const result = await apiHandler.post<
        CraftingApiResponseDefinition,
        never,
        CraftItemRequestDefinition
      >(url, request);

      setCraftingResponse(result);

      if (result.crafted_item) {
        setSuccessMessage(`You successfully crafted ${selectedItem.name}.`);
      } else {
        setError(`You failed to craft ${selectedItem.name}.`);
      }
    } catch (requestError) {
      setError(getErrorMessage(requestError));
    } finally {
      setIsCrafting(false);
    }
  };

  const clearMessages = () => {
    setError(null);
    setSuccessMessage(null);
    setCraftingResponse(null);
  };

  return {
    isCrafting,
    error,
    successMessage,
    craftingResponse,
    craftItem,
    clearMessages,
  };
};
