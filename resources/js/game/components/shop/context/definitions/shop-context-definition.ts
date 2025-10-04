import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import React from 'react';

import { StateSetter } from '../../../../../types/state-setter-type';
import { EquippableItemWithBase } from '../../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';
import UsePurchaseItemRequestDefinition from '../../api/hooks/definitions/use-purchase-item-request-definition';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export default interface ShopContextDefinition {
  data: EquippableItemWithBase[];
  loading: boolean;
  error: AxiosErrorDefinition | null;
  purchaseError: AxiosErrorDefinition | null;
  purchaseSuccessMessage: string | null;
  purchaseLoading: boolean;
  handleScroll: (e: React.UIEvent<HTMLDivElement>) => void;
  searchText: string;
  setSearchText: (txt: string) => void;
  selectedCost: DropdownItem | null;
  setSelectedCost: (opt: DropdownItem | null) => void;
  selectedType: DropdownItem | null;
  setSelectedType: (opt: DropdownItem | null) => void;
  setShopPurchaseRequestParams: StateSetter<UsePurchaseItemRequestDefinition>;
  inventoryIsFull: boolean;
}
