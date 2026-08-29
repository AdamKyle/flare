import { ItemScreens } from './item-screen-constants';

export type ItemListScreenProps = Record<string, never>;

export interface ItemShowScreenProps {
  item_id: number;
}

export interface ItemFormScreenProps {
  item_id: number | null;
}

export interface ItemScreenPropsMap {
  [ItemScreens.LIST]: ItemListScreenProps;
  [ItemScreens.SHOW]: ItemShowScreenProps;
  [ItemScreens.FORM]: ItemFormScreenProps;
}

export type ItemScreenName = keyof ItemScreenPropsMap;
export type ItemScreenPropsOf<K extends ItemScreenName> = ItemScreenPropsMap[K];
