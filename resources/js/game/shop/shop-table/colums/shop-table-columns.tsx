import React from "react";
import {formatNumber} from "../../../lib/game/format-number";
import {ShopItemTypes} from "../enums/shop-item-types";
import PrimaryLinkButton from "../../../components/ui/buttons/primary-link-button";

export default class ShopTableColumns {

    private WEAPON_TYPES = [
        ShopItemTypes.WEAPON,
        ShopItemTypes.BOW,
        ShopItemTypes.FAN,
        ShopItemTypes.GUN,
        ShopItemTypes.HAMMER,
        ShopItemTypes.STAVE,
    ];

    private ARMOUR_TYPES = [
        ShopItemTypes.BODY,
        ShopItemTypes.BOOTS,
        ShopItemTypes.GLOVES,
        ShopItemTypes.HELMET,
        ShopItemTypes.LEGGINGS,
        ShopItemTypes.SLEEVES,
        ShopItemTypes.SHIELD,
    ];

    public buildColumns(itemType?: ShopItemTypes) {
        let shopColumns: any[] = [
            {
                name: 'Name',
                selector: (row: { name: string; id: number, type: string}) => row.name,
                cell: (row: any) => <span>
                    <PrimaryLinkButton button_label={row.name} on_click={() => this.navigateToItemShow(row.id)} additional_css={'text-gray-600 hover:text-gray-700 dark:text-gray-300 dark:hover:text-gray-400'} />
                </span>
            },
            {
                name: 'Type',
                selector: (row: { type: string; }) => row.type,
                sortable: true,
            },
        ];

        if (typeof itemType === 'undefined') {
            shopColumns = [...shopColumns, ...this.getWeaponColumns(), ...this.getArmourColumns()];
        } else {

            const isWeaponType = this.WEAPON_TYPES.filter((weaponType: ShopItemTypes) => {
                return weaponType === itemType
            }).length > 0;

            const isArmorType = this.ARMOUR_TYPES.filter((armorType: ShopItemTypes) => {
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
            selector: (row: { cost: number; }) => row.cost,
            sortable: true,
            format: (row: any) => formatNumber(row.cost)
        })

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

    protected navigateToItemShow(itemId: number): void {
        window.open('/items/' + itemId, '_blank');
    }
}
