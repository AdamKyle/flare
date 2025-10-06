import { debounce, isNil } from 'lodash';
import React, { useEffect, useMemo, useState } from 'react';

import ShopBuyMany from './components/shop-buy-many';
import ShopCard from './components/shop-card';
import ShopComparison from './components/shop-comparison';
import ShopItemView from './components/shop-item-view';
import { ShopContext } from './context/shop-context';
import ShopProps from './types/shop-props';
import { buildShopItemTypeSelection } from './utils/build-shop-item-type-selection';
import { useCustomContext } from '../../../utils/hooks/use-custom-context';
import { EquippableItemWithBase } from '../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';
import { formatNumberWithCommas } from '../../util/format-number';

import CharacterSheetDefinition from 'game-data/api-data-definitions/character/character-sheet-definition';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Card from 'ui/cards/card';
import ContainerWithTitle from 'ui/container/container-with-title';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import InfiniteRow from 'ui/infinite-scroll/components/infitnite-row';
import Input from 'ui/input/input';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import Separator from 'ui/separator/separator';

const Shop = ({ close_shop }: ShopProps) => {
  const {
    data,
    loading,
    error,
    handleScroll,
    searchText,
    setSearchText,
    selectedCost,
    setSelectedCost,
    selectedType,
    setSelectedType,
    setShopPurchaseRequestParams,
    purchaseLoading,
    purchaseError,
    purchaseSuccessMessage,
    inventoryIsFull,
    character,
    updateCharacter,
  } = useCustomContext(ShopContext, 'Shop');

  const [itemToView, setItemToView] = useState<EquippableItemWithBase | null>(
    null
  );
  const [buyManyItem, setBuyManyItem] = useState<EquippableItemWithBase | null>(
    null
  );
  const [itemToCompare, setItemToCompare] =
    useState<EquippableItemWithBase | null>(null);
  const [localSearch, setLocalSearch] = useState<string>(searchText);
  const [
    purchaseAndReplaceSuccessMessage,
    setPurchaseAndReplaceSuccessMessage,
  ] = useState<string | null>(null);

  useEffect(() => {
    setLocalSearch(searchText);
  }, [searchText]);

  const debouncedSetSearchText = useMemo(
    () => debounce((v: string) => setSearchText(v), 300),
    [setSearchText]
  );

  const handleSearch = (value: string) => {
    setLocalSearch(value);
    debouncedSetSearchText(value.trim());
  };

  const costOptions = useMemo<DropdownItem[]>(
    () => [
      { label: 'Cost: Low to High', value: 'asc' },
      { label: 'Cost: High to Low', value: 'desc' },
    ],
    []
  );

  const typeOptions = useMemo<DropdownItem[]>(
    () => buildShopItemTypeSelection(),
    []
  );

  const preSelectedCost = useMemo<DropdownItem | undefined>(
    () => costOptions.find((o) => o.value === selectedCost?.value),
    [costOptions, selectedCost]
  );

  const preSelectedType = useMemo<DropdownItem | undefined>(
    () => typeOptions.find((o) => o.value === selectedType?.value),
    [typeOptions, selectedType]
  );

  const handleCostChange = (opt: DropdownItem) => {
    setSelectedCost(opt);
  };

  const handleClearCost = () => {
    setSelectedCost(null);
  };

  const handleTypeChange = (opt: DropdownItem) => {
    setSelectedType(opt);
  };

  const handleClearType = () => {
    setSelectedType(null);
  };

  const handleViewItem = (item_id: number) => {
    const found = data.find((item) => item.item_id === item_id);

    if (found) {
      setItemToView(found);
    }
  };

  const handleViewBuyMany = (item_id: number) => {
    const found = data.find((item) => item.item_id === item_id);

    if (found) {
      setBuyManyItem(found);
    }
  };

  const handleCompareItem = (item_id: number) => {
    const found = data.find((item) => item.item_id === item_id);

    if (found) {
      setItemToCompare(found);
    }
  };

  const handleBuyItem = (item_id: number) => {
    setShopPurchaseRequestParams({
      item_id: item_id,
    });
  };

  const closeItemView = () => {
    setItemToView(null);
  };

  const closeItemComparison = () => {
    setItemToCompare(null);
  };

  const closeBuyMany = () => {
    setBuyManyItem(null);
  };

  const handleBuyAndReplaceSuccess = (
    successMessage: string,
    character: Partial<CharacterSheetDefinition>
  ) => {
    closeItemComparison();

    updateCharacter(character);

    setPurchaseAndReplaceSuccessMessage(successMessage);
  };

  const renderContent = () => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error) {
      return <Alert variant={AlertVariant.DANGER}>{error.message}</Alert>;
    }

    return (
      <InfiniteRow handle_scroll={handleScroll} additional_css="max-h-[500px]">
        {data.map((item) => (
          <ShopCard
            key={item.item_id}
            item={item}
            view_item={handleViewItem}
            compare_item={handleCompareItem}
            view_buy_many={handleViewBuyMany}
            on_purchase_item={handleBuyItem}
            is_actions_disabled={purchaseLoading || inventoryIsFull}
          />
        ))}
      </InfiniteRow>
    );
  };

  const renderCharacterGold = () => {
    if (!character) {
      return null;
    }

    return (
      <p className="mb-4 text-gray-800 dark:text-gray-300">
        <strong>
          <span className="text-marigold-600 dark:text-mango-tango-400">
            Your Gold:
          </span>
        </strong>{' '}
        {formatNumberWithCommas(character.gold)}
      </p>
    );
  };

  const renderPurchaseLoading = () => {
    if (!purchaseLoading) {
      return null;
    }

    return (
      <>
        <Separator />
        <InfiniteLoader />
        <Separator />
      </>
    );
  };

  const renderPurchaseError = () => {
    if (!purchaseError) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.DANGER} closable>
        {purchaseError.message}
      </Alert>
    );
  };

  const renderPurchaseSuccess = () => {
    if (!purchaseSuccessMessage) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.SUCCESS} closable>
        {purchaseSuccessMessage}
      </Alert>
    );
  };

  const renderPurchaseAndReplaceSuccess = () => {
    if (!purchaseAndReplaceSuccessMessage) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.SUCCESS} closable>
        {purchaseAndReplaceSuccessMessage}
      </Alert>
    );
  };

  const renderInventoryIsFullNotice = () => {
    if (!inventoryIsFull) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.WARNING}>
        Your inventory is currently full. You cannot purchase any items, the
        shop keeper is sad.
      </Alert>
    );
  };

  const renderNoGoldNotice = () => {
    if (!character) {
      return null;
    }

    if (character.gold > 0) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.WARNING}>
        You have no gold. You cannot buy anything. There is a great saddness in
        the air.
      </Alert>
    );
  };

  if (!isNil(itemToCompare)) {
    return (
      <ShopComparison
        close_comparison={closeItemComparison}
        item_name={itemToCompare.name}
        item_type={itemToCompare.type}
        on_purchase_and_replace_success={handleBuyAndReplaceSuccess}
      />
    );
  }

  if (!isNil(itemToView)) {
    return <ShopItemView item={itemToView} close_view={closeItemView} />;
  }

  if (!isNil(buyManyItem)) {
    return <ShopBuyMany on_close={closeBuyMany} item={buyManyItem} />;
  }

  return (
    <ContainerWithTitle manageSectionVisibility={close_shop} title="Shop">
      <Card>
        <p className="my-4 italic text-gray-800 dark:text-gray-300">
          Welcome to my humble shop. What can I get you? If you are looking to
          replace something, click Compare, you might find the item is better
          and you can simply replace it, for a fee of course.
        </p>
        {renderCharacterGold()}
        {renderInventoryIsFullNotice()}
        {renderNoGoldNotice()}
        {renderPurchaseError()}
        {renderPurchaseSuccess()}
        {renderPurchaseAndReplaceSuccess()}
        {renderPurchaseLoading()}
        <div className="flex flex-col md:flex-row md:items-center gap-4 pt-2 px-4">
          <div className="flex-1">
            <Input
              value={localSearch}
              on_change={handleSearch}
              place_holder="Search shop items"
              clearable
              disabled={purchaseLoading}
            />
          </div>
          <div className="w-full md:w-48">
            <Dropdown
              items={costOptions}
              pre_selected_item={preSelectedCost}
              on_select={handleCostChange}
              on_clear={handleClearCost}
              selection_placeholder="Sort by cost"
              all_click_outside
              disabled={purchaseLoading}
            />
          </div>
          <div className="w-full md:w-48">
            <Dropdown
              items={typeOptions}
              pre_selected_item={preSelectedType}
              on_select={handleTypeChange}
              on_clear={handleClearType}
              selection_placeholder="Filter by type"
              all_click_outside
              disabled={purchaseLoading}
            />
          </div>
        </div>
        <Separator />
        {renderContent()}
      </Card>
    </ContainerWithTitle>
  );
};

export default Shop;
