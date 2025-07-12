import { ItemBaseTypes } from '../enums/item-base-type';

export type ItemBaseType = (typeof ItemBaseTypes)[keyof typeof ItemBaseTypes];
