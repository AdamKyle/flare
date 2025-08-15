import { debounce } from 'lodash';
import { isNil } from 'lodash';
import React, { useMemo, useState, useEffect } from 'react';

import ShopCard from './components/shop-card';
import ShopComparison from './components/shop-comparison';
import { ShopContext } from './context/shop-context';
import ShopProps from './types/shop-props';
import { buildShopItemTypeSelection } from './utils/build-shop-item-type-selection';
import { useCustomContext } from '../../../utils/hooks/use-custom-context';
import ItemDetails from '../../api-definitions/items/item-details';

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
  } = useCustomContext(ShopContext, 'Shop');

  const [itemToView, setItemToView] = useState<ItemDetails | null>(null);
  const [itemToCompare, setItemToCompare] = useState<ItemDetails | null>(null);
  const [localSearch, setLocalSearch] = useState<string>(searchText);

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
    const found = data.find((it) => it.id === item_id);
    if (found) {
      setItemToView(found);
    }
  };

  const handleCompareItem = (item_id: number) => {
    const found = data.find((it) => it.id === item_id);
    if (found) {
      setItemToCompare(found);
    }
  };

  const closeItemView = () => {
    setItemToView(null);
  };

  const closeItemComparison = () => {
    setItemToCompare(null);
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
            key={item.id}
            item={item}
            view_item={handleViewItem}
            compare_item={handleCompareItem}
          />
        ))}
      </InfiniteRow>
    );
  };

  if (!isNil(itemToCompare)) {
    return (
      <ShopComparison
        close_comparison={closeItemComparison}
        item_name={itemToCompare.name}
        item_type={itemToCompare.type}
      />
    );
  }

  return (
    <ContainerWithTitle manageSectionVisibility={close_shop} title="Shop">
      <Card>
        <p className="my-4 italic text-gray-800 dark:text-gray-300">
          Welcome to mu humble shop. What can I get you?
        </p>
        <div className="flex flex-col md:flex-row md:items-center gap-4 pt-2 px-4">
          <div className="flex-1">
            <Input
              value={localSearch}
              on_change={handleSearch}
              place_holder="Search shop items"
              clearable
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
