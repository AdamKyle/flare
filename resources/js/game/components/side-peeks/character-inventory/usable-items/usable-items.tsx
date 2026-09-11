import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { AnimatePresence } from 'framer-motion';
import { debounce } from 'lodash';
import React, { useEffect, useMemo, useState } from 'react';

import { useUseAlchemyItemApi } from './api/hooks/use-use-alchemy-item-api';
import { useUseManyAlchemyItemsApi } from './api/hooks/use-use-many-alchemy-items-api';
import SpecialUsableItemGuidance from './components/special-usable-item-guidance';
import UsableItemsProps from './types/usable-items-props';
import UsableItem from './usable-item';
import {
  isSelfUseAlchemyBoonItem,
  resolveAlchemyLegalUseCount,
} from './utils/resolve-alchemy-legal-use-count';
import BaseUsableItemDefinition from '../../../../api-definitions/items/usable-item-definitions/base-usable-item-definition';
import { useInfiniteScroll } from '../../../character-sheet/partials/character-inventory/hooks/use-infinite-scroll';
import UsableItemsList from '../../components/items/usable-items-list';
import { CharacterInventoryApiUrls } from '../api/enums/character-inventory-api-urls';

import { GameDataError } from 'game-data/components/game-data-error';
import { useGameData } from 'game-data/hooks/use-game-data';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import StackedCard from 'ui/cards/stacked-card';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import NumberField from 'ui/forms/number-field';
import Input from 'ui/input/input';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import { useSidePeekOptions } from 'ui/side-peek/options/hooks/use-side-peek-options';
import SidePeekOptionDefinition from 'ui/side-peek/options/types/side-peek-option-definition';

const UsableItems = ({
  character_id,
  initial_item,
  initial_search_text,
}: UsableItemsProps) => {
  const { gameData } = useGameData();
  const activeBoons = gameData?.character?.active_boons ?? [];

  const [itemToView, setItemToView] = useState<BaseUsableItemDefinition | null>(
    initial_item ?? null
  );
  const [mutationSource, setMutationSource] = useState<
    'list' | 'detail' | null
  >(null);
  const [detailQuantityMode, setDetailQuantityMode] = useState(false);
  const [detailQuantity, setDetailQuantity] = useState(1);

  const {
    data,
    error,
    loading,
    setSearchText,
    setFilters,
    onEndReached,
    setRefresh,
  } = UsePaginatedApiHandler<BaseUsableItemDefinition>({
    url: CharacterInventoryApiUrls.CHARACTER_USABLE_ITEMS,
    urlParams: { character: character_id },
    initialSearchText: initial_search_text,
  });

  const handleUseSuccess = (): void => {
    setRefresh((previousValue) => !previousValue);

    if (mutationSource === 'detail') {
      setItemToView(null);
    }

    setDetailQuantityMode(false);
    setDetailQuantity(1);
    setMutationSource(null);
  };

  const {
    using,
    error: useError,
    successMessage: useSuccessMessage,
    useAlchemyItem: submitAlchemyItemUse,
  } = useUseAlchemyItemApi({
    characterId: character_id,
    onSuccess: handleUseSuccess,
  });

  const {
    using: usingMany,
    error: useManyError,
    useManyItems: submitManyItemsUse,
  } = useUseManyAlchemyItemsApi({
    characterId: character_id,
    onSuccess: handleUseSuccess,
  });

  const [actingSlotId, setActingSlotId] = useState<number | null>(null);

  useEffect(() => {
    if (!initial_item) {
      return;
    }

    setItemToView(initial_item);
  }, [initial_item]);

  useEffect(() => {
    setDetailQuantityMode(false);
    setDetailQuantity(1);
  }, [itemToView?.slot_id]);

  const debouncedSetSearchText = useMemo(
    () => debounce((value: string) => setSearchText(value), 300),
    // eslint-disable-next-line react-hooks/exhaustive-deps
    []
  );

  const onSearch = (value: string) => {
    debouncedSetSearchText(value.trim());
  };

  const onViewItem = (itemId: number) => {
    const foundItem = data.find((item) => item.item_id === itemId);

    if (!foundItem) {
      return;
    }

    setItemToView(foundItem);
  };

  const onCloseViewItem = () => {
    setItemToView(null);
    setDetailQuantityMode(false);
    setDetailQuantity(1);
  };

  const { handleScroll: handleInventoryScroll } = useInfiniteScroll({
    on_end_reached: onEndReached,
  });

  const handleFilterChange = (dropDownValue: DropdownItem) => {
    setFilters({
      [dropDownValue.value]: true,
    });
  };

  const handleClearFilters = () => {
    setFilters({});
  };

  const handleUseOne = (slotId: number, source: 'list' | 'detail'): void => {
    setActingSlotId(slotId);
    setMutationSource(source);
    void submitAlchemyItemUse(slotId, false);
  };

  const handleUseAll = (slotId: number, source: 'list' | 'detail'): void => {
    setActingSlotId(slotId);
    setMutationSource(source);
    void submitAlchemyItemUse(slotId, true);
  };

  const handleUseQuantity = (
    slotId: number,
    quantity: number,
    source: 'list' | 'detail'
  ): void => {
    setActingSlotId(slotId);
    setMutationSource(source);
    void submitManyItemsUse(Array(quantity).fill(slotId));
  };

  const usingSlotId = using || usingMany ? actingSlotId : null;

  const detailLegalUseCount = itemToView
    ? resolveAlchemyLegalUseCount(itemToView, activeBoons, new Date())
    : 0;

  const detailOptions: SidePeekOptionDefinition[] = useMemo(() => {
    if (
      !itemToView ||
      itemToView.slot_id === null ||
      !isSelfUseAlchemyBoonItem(itemToView)
    ) {
      return [];
    }

    const slotId = itemToView.slot_id;
    const isMutating = using || usingMany;

    if (!itemToView.can_stack) {
      if (detailLegalUseCount < 1) {
        return [];
      }

      return [
        {
          id: 'use-item',
          label: 'Use Item',
          loading_label: 'Using Item...',
          variant: ButtonVariant.ALCHEMY,
          loading: isMutating,
          on_click: () => handleUseOne(slotId, 'detail'),
        },
      ];
    }

    if (itemToView.amount === 1) {
      if (detailLegalUseCount !== 1) {
        return [];
      }

      return [
        {
          id: 'use-item',
          label: 'Use Item',
          loading_label: 'Using Item...',
          variant: ButtonVariant.ALCHEMY,
          loading: isMutating,
          on_click: () => handleUseOne(slotId, 'detail'),
        },
      ];
    }

    if (detailQuantityMode) {
      return [
        {
          id: 'use-quantity',
          label: `Use ${detailQuantity}`,
          loading_label: `Using ${detailQuantity}...`,
          variant: ButtonVariant.ALCHEMY,
          loading: isMutating,
          on_click: () => handleUseQuantity(slotId, detailQuantity, 'detail'),
        },
        {
          id: 'cancel-quantity',
          label: 'Cancel',
          variant: ButtonVariant.DANGER,
          disabled: isMutating,
          on_click: () => {
            setDetailQuantityMode(false);
            setDetailQuantity(1);
          },
        },
      ];
    }

    const options: SidePeekOptionDefinition[] = [];

    if (detailLegalUseCount >= 1) {
      options.push({
        id: 'use-x',
        label: 'Use X',
        variant: ButtonVariant.ALCHEMY,
        disabled: isMutating,
        on_click: () => {
          setDetailQuantity(1);
          setDetailQuantityMode(true);
        },
      });
    }

    if (detailLegalUseCount === itemToView.amount && itemToView.amount > 1) {
      options.push({
        id: 'use-all',
        label: 'Use All',
        loading_label: 'Using All...',
        variant: ButtonVariant.ALCHEMY,
        loading: isMutating,
        on_click: () => handleUseAll(slotId, 'detail'),
      });
    }

    return options;
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [
    itemToView,
    detailLegalUseCount,
    detailQuantityMode,
    detailQuantity,
    using,
    usingMany,
  ]);

  useSidePeekOptions(detailOptions);

  if (error) {
    return (
      <div className={'p-4'}>
        <GameDataError />
      </div>
    );
  }

  if (loading && data.length === 0) {
    return (
      <div className={'p-4'}>
        <InfiniteLoader />
      </div>
    );
  }

  const renderDetailQuantityControl = () => {
    if (!detailQuantityMode || !itemToView || itemToView.slot_id === null) {
      return null;
    }

    return (
      <NumberField
        id={`detail-use-quantity-${itemToView.slot_id}`}
        label="Use quantity"
        value={String(detailQuantity)}
        on_change={(value) => {
          const parsed = parseInt(value, 10);

          if (Number.isNaN(parsed)) {
            setDetailQuantity(1);

            return;
          }

          setDetailQuantity(
            Math.min(Math.max(parsed, 1), Math.max(detailLegalUseCount, 1))
          );
        }}
        min={1}
        max={detailLegalUseCount}
        disabled={using || usingMany}
      />
    );
  };

  const renderUsableItemView = () => {
    if (!itemToView) {
      return null;
    }

    const isSpecialItem =
      itemToView.holy_level !== null || itemToView.damages_kingdoms;

    return (
      <StackedCard on_close={onCloseViewItem}>
        <div className="flex flex-col gap-3">
          <UsableItem item={itemToView} />
          {(useError || useManyError || useSuccessMessage) && (
            <Alert
              variant={
                useError || useManyError
                  ? AlertVariant.DANGER
                  : AlertVariant.SUCCESS
              }
            >
              {useError ?? useManyError ?? useSuccessMessage}
            </Alert>
          )}
          {isSpecialItem && <SpecialUsableItemGuidance item={itemToView} />}
          {renderDetailQuantityControl()}
        </div>
      </StackedCard>
    );
  };

  return (
    <>
      <div className="flex h-full flex-col overflow-hidden">
        <div className="px-4 pt-4">
          <Input
            on_change={onSearch}
            place_holder={'Search items'}
            default_value={initial_search_text ?? null}
            clearable
          />
        </div>
        <div className="mt-4 px-4 pb-4">
          <Dropdown
            items={[
              { label: 'Increase Stats', value: 'increase-stats' },
              { label: 'Effects Skills', value: 'effects-skills' },
              {
                label: 'Effects Base Modifiers',
                value: 'effects-base-modifiers',
              },
              { label: 'Damages Kingdoms', value: 'damages-kingdoms' },
              { label: 'Holy Oils', value: 'holy-oils' },
              { label: 'Gem Scrolls', value: 'scrolls' },
              { label: 'Gem XP Scrolls', value: 'xp-scrolls' },
              { label: 'Gem Currency Scrolls', value: 'currency-scrolls' },
              { label: 'Gem Item Scrolls', value: 'item-scrolls' },
            ]}
            selection_placeholder={'Filter items by'}
            on_select={handleFilterChange}
            on_clear={handleClearFilters}
          />
        </div>
        <div className="min-h-0 flex-1" aria-busy={loading}>
          <UsableItemsList
            items={data}
            on_scroll_to_end={handleInventoryScroll}
            on_item_clicked={onViewItem}
            active_boons={activeBoons}
            using_slot_id={usingSlotId}
            on_use_one={(slotId) => handleUseOne(slotId, 'list')}
            on_use_quantity={(slotId, quantity) =>
              handleUseQuantity(slotId, quantity, 'list')
            }
            on_use_all={(slotId) => handleUseAll(slotId, 'list')}
          />
        </div>
      </div>
      <AnimatePresence mode="wait">{renderUsableItemView()}</AnimatePresence>
    </>
  );
};

export default UsableItems;
