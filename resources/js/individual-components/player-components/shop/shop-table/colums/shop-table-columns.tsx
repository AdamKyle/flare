import React from "react";
import {formatNumber} from "../../../../../game/lib/game/format-number";
import {ItemType} from "../../../../../game/components/items/enums/item-type";
import PrimaryLinkButton from "../../../../../game/components/ui/buttons/primary-link-button";
import ItemDefinition from "../../../../../game/components/items/deffinitions/item-definition";
import PrimaryButton from "../../../../../game/components/ui/buttons/primary-button";
import SuccessButton from "../../../../../game/components/ui/buttons/success-button";
import {shopServiceContainer} from "../../container/shop-container";
import ShopAjax, {SHOP_ACTIONS} from "../../ajax/shop-ajax";
import Shop from "../../shop";

type OnClick = (itemId: number) => void;
type BuyMany = (item: ItemDefinition) => void;
type Comparison = (item: ItemDefinition) => void;

export default class ShopTableColumns {

    private WEAPON_TYPES = [
        ItemType.WEAPON,
        ItemType.BOW,
        ItemType.FAN,
        ItemType.GUN,
        ItemType.HAMMER,
        ItemType.STAVE,
    ];

    private ARMOUR_TYPES = [
        ItemType.BODY,
        ItemType.BOOTS,
        ItemType.GLOVES,
        ItemType.HELMET,
        ItemType.LEGGINGS,
        ItemType.SLEEVES,
        ItemType.SHIELD,
    ];

    private ajax: ShopAjax;

    private component?: Shop;

    constructor() {
        this.ajax = shopServiceContainer().fetch(ShopAjax);
    }

    setComponent(component: Shop): ShopTableColumns {
        this.component = component;

        return this;
    }

    viewPurchaseAny(item: ItemDefinition, viewBuyMany: BuyMany) {
        return viewBuyMany(item);
    }

    viewComparison(item: ItemDefinition, viewComparison: Comparison) {
        return viewComparison(item);
    }

    public buildColumns(onClick: OnClick, viewBuyMany: BuyMany, viewComparison: Comparison, itemType?: ItemType) {
        let shopColumns: any[] = [
            {
                name: 'Name',
                selector: (row: ItemDefinition) => row.name,
                cell: (row: any) => <span>
                    <PrimaryLinkButton button_label={row.name} on_click={() => onClick(row.id)} additional_css={'text-gray-600 hover:text-gray-700 dark:text-gray-300 dark:hover:text-gray-400'} />
                </span>
            },
            {
                name: 'Type',
                selector: (row: ItemDefinition) => row.type,
                sortable: true,
            },
        ];

        if (typeof itemType === 'undefined') {
            shopColumns = [...shopColumns, ...this.getWeaponColumns(), ...this.getArmourColumns()];
        } else {

            const isWeaponType = this.WEAPON_TYPES.filter((weaponType: ItemType) => {
                return weaponType === itemType
            }).length > 0;

            const isArmorType = this.ARMOUR_TYPES.filter((armorType: ItemType) => {
                return armorType === itemType
            }).length > 0;

            if (isWeaponType) {
                shopColumns = [...shopColumns, ...this.getWeaponColumns()];
            }

            if (isArmorType) {
                shopColumns = [...shopColumns, ...this.getArmourColumns()];
            }
        }

        shopColumns.push({
            name: 'Cost',
            selector: (row: ItemDefinition) => row.cost,
            sortable: true,
            format: (row: any) => formatNumber(row.cost)
        })

        shopColumns = [
            ...shopColumns,
            {
                name: 'Actions',
                selector: (row: ItemDefinition) => row.name,
                cell: (row: ItemDefinition) => <div className={'my-2'}>
                    <div className="w-full mb-2">
                        <PrimaryButton button_label={'Buy'} on_click={() => this.buyItem(row)} additional_css={'w-full'} />
                    </div>
                    <div className="w-full mb-2">
                        <PrimaryButton button_label={'Compare'} on_click={() => this.viewComparison(row, viewComparison)} additional_css={'w-full'} />
                    </div>
                    <div className="w-full">
                        <SuccessButton button_label={'Buy Multiple'} on_click={() => this.viewPurchaseAny(row, viewBuyMany)} additional_css={'w-full'} />
                    </div>
                </div>
            },
        ]

        return shopColumns;
    }

    protected getWeaponColumns() {
        return [
            {
                name: 'Attack',
                selector: (row: { base_damage: number; }) => row.base_damage,
                sortable: true,
                format: (row: any) => formatNumber(row.base_damage)
            },
        ];
    }

    protected getArmourColumns() {
        return [
            {
                name: 'Attack',
                selector: (row: { base_ac: number; }) => row.base_ac,
                sortable: true,
                format: (row: any) => formatNumber(row.base_ac)
            },
        ];
    }

    private buyItem(row: ItemDefinition) {

        if (typeof this.component !== 'undefined') {
            this.component.setState({
                error_message: null,
                success_message: null,
            }, () => {
                if (typeof this.component === 'undefined') {
                    return;
                }

                this.ajax.doShopAction(this.component, SHOP_ACTIONS.BUY, {
                    item_id: row.id,
                });
            })
        }
    }
}
