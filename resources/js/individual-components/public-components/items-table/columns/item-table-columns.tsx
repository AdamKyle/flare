import React from "react";
import ItemDefinition from "../../../../game/components/items/deffinitions/item-definition";
import PrimaryLinkButton from "../../../../game/components/ui/buttons/primary-link-button";
import { formatNumber } from "../../../../game/lib/game/format-number";
import { TableType } from "../types/table-type";

type OnClick = (itemId: number) => void;

export default class ItemTableColumns {
    public buildColumns(onClick: OnClick, tableType: string) {
        let itemsTableColumns: any[] = [
            {
                name: "Name",
                selector: (row: ItemDefinition) => row.name,
                cell: (row: any) => (
                    <span>
                        <PrimaryLinkButton
                            button_label={row.name}
                            on_click={() => onClick(row.id)}
                            additional_css={
                                "text-gray-600 hover:text-gray-700 dark:text-gray-300 dark:hover:text-gray-400"
                            }
                        />
                    </span>
                ),
            },
            {
                name: "Type",
                selector: (row: ItemDefinition) => row.type,
                sortable: true,
            },
        ];

        itemsTableColumns = [
            ...itemsTableColumns,
            ...this.getWeaponColumns(),
            ...this.getArmourColumns(),
            ...this.getHealingColumns(),
        ];

        if (tableType === TableType.CRAFTING) {
            itemsTableColumns.push({
                name: "Cost (Gold)",
                selector: (row: ItemDefinition) => formatNumber(row.cost),
                sortable: true,
            });

            itemsTableColumns.push({
                name: "Crafting Type",
                selector: (row: ItemDefinition) => row.crafting_type,
                sortable: true,
            });

            itemsTableColumns.push({
                name: "Skill Level Required",
                selector: (row: ItemDefinition) => row.skill_level_req,
                sortable: true,
            });

            itemsTableColumns.push({
                name: "Skill Level Trivial",
                selector: (row: ItemDefinition) => row.skill_level_trivial,
                sortable: true,
            });
        }

        return itemsTableColumns;
    }

    protected getWeaponColumns() {
        return [
            {
                name: "Attack",
                selector: (row: { raw_damage: number }) => row.raw_damage,
                sortable: true,
                format: (row: any) => formatNumber(row.raw_damage),
            },
        ];
    }

    protected getHealingColumns() {
        return [
            {
                name: "Healing",
                selector: (row: { raw_healing: number }) => row.raw_healing,
                sortable: true,
                format: (row: any) => formatNumber(row.raw_healing),
            },
        ];
    }

    protected getArmourColumns() {
        return [
            {
                name: "AC",
                selector: (row: { raw_ac: number }) => row.raw_ac,
                sortable: true,
                format: (row: any) => formatNumber(row.raw_ac),
            },
        ];
    }
}
