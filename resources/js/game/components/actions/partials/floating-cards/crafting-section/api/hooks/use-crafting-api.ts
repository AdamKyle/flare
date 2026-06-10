import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError } from 'axios';
import { useEffect, useRef, useState } from 'react';

import { normalizeCraftingType } from '../../utils/normalize-crafting-type';
import CraftableItemDefinition from '../definitions/craftable-item-definition';
import CraftingApiResponseDefinition from '../definitions/crafting-api-response-definition';
import {
  CraftingFiltersDefinition,
  FetchCraftingItemsRequestDefinition,
} from '../definitions/crafting-request-definition';
import CraftingRequestDefinition from '../definitions/crafting-request-definition';
import { CraftingApiUrls } from '../enums/crafting-api-urls';
import UseCraftingApiDefinition from './definitions/use-crafting-api-definition';
import UseCraftingApiParams from './definitions/use-crafting-api-params';

const ITEMS_PER_PAGE = 10;

const getErrorMessage = (error: unknown): string => {
  if (error instanceof AxiosError) {
    return error.response?.data?.message ?? 'Unable to complete the request.';
  }

  return 'Unable to complete the request.';
};

export const useCraftingApi = ({
  characterId,
  selectedType,
  armourType,
  searchText,
}: UseCraftingApiParams): UseCraftingApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const [items, setItems] = useState<CraftableItemDefinition[]>([]);
  const [craftingData, setCraftingData] =
    useState<CraftingApiResponseDefinition | null>(null);
  const [loading, setLoading] = useState(false);
  const [isLoadingMore, setIsLoadingMore] = useState(false);
  const [isCrafting, setIsCrafting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const currentPageRef = useRef(1);

  const filters: CraftingFiltersDefinition =
    selectedType === 'armour' && armourType ? { armour_type: armourType } : {};

  const canFetch = Boolean(
    characterId > 0 && selectedType && (selectedType !== 'armour' || armourType)
  );

  const fetchPage = async (page: number, append: boolean) => {
    if (!canFetch || !selectedType) {
      setItems([]);
      setCraftingData(null);

      return;
    }

    if (append) {
      setIsLoadingMore(true);
    } else {
      setLoading(true);
    }

    setError(null);

    const url = getUrl(
      selectedType === 'for-class'
        ? CraftingApiUrls.FETCH_FOR_CLASS
        : CraftingApiUrls.FETCH_ITEMS,
      { character: characterId }
    );

    const requestParams: FetchCraftingItemsRequestDefinition = {
      per_page: ITEMS_PER_PAGE,
      page,
      search_text: searchText,
      filters,
    };

    if (selectedType !== 'for-class') {
      requestParams.crafting_type = selectedType;
    }

    try {
      const result = await apiHandler.get<
        CraftingApiResponseDefinition,
        FetchCraftingItemsRequestDefinition
      >(url, {
        params: requestParams,
      });

      setItems((previousItems) =>
        append ? [...previousItems, ...result.data] : result.data
      );
      setCraftingData(result);
      currentPageRef.current = page;
    } catch (requestError) {
      setError(getErrorMessage(requestError));
    } finally {
      setLoading(false);
      setIsLoadingMore(false);
    }
  };

  const fetchFirstPage = () => {
    currentPageRef.current = 1;
    fetchPage(1, false).catch(() => {});
  };

  useEffect(() => {
    fetchFirstPage();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [characterId, selectedType, armourType, searchText]);

  const loadMore = () => {
    if (
      !craftingData?.meta.can_load_more ||
      loading ||
      isLoadingMore ||
      isCrafting
    ) {
      return;
    }

    fetchPage(currentPageRef.current + 1, true).catch(() => {});
  };

  const craftItem = async (
    item: CraftableItemDefinition,
    craftForNpc: boolean,
    craftForEvent: boolean
  ) => {
    setIsCrafting(true);
    setError(null);
    setSuccessMessage(null);

    const url = getUrl(CraftingApiUrls.CRAFT_ITEM, {
      character: characterId,
    });

    const request: CraftingRequestDefinition = {
      item_to_craft: item.id,
      type: normalizeCraftingType(item),
      craft_for_npc: craftForNpc,
      craft_for_event: craftForEvent,
      per_page: ITEMS_PER_PAGE,
      page: 1,
      search_text: searchText,
      filters,
    };

    try {
      const result = await apiHandler.post<
        CraftingApiResponseDefinition,
        never,
        CraftingRequestDefinition
      >(url, request);

      setItems(result.data);
      setCraftingData(result);
      currentPageRef.current = 1;

      if (result.crafted_item) {
        setSuccessMessage(`You successfully crafted ${item.name}.`);
      } else {
        setError(`You failed to craft ${item.name}.`);
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
  };

  return {
    items,
    craftingData,
    loading,
    isLoadingMore,
    isCrafting,
    error,
    successMessage,
    fetchFirstPage,
    loadMore,
    craftItem,
    clearMessages,
  };
};
