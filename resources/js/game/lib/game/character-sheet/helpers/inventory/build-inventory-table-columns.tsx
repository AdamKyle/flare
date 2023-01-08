import React, {Component, MouseEventHandler} from "react";
import ItemNameColorationButton from "../../../../../components/ui/item-name-coloration-button";
import { formatNumber } from "../../../format-number";
import ActionsInterface from "./actions-interface";
import InventoryDetails from "../../types/inventory/inventory-details";
import UsableItemsDetails from "../../types/inventory/usable-items-details";

/**
 * Build Inventory Table Columns
 *
 * @param component
 * @param clickAction
 * @param componentName
 * @constructor
 */
export const BuildInventoryTableColumns = (component?: ActionsInterface, clickAction?: (item?: InventoryDetails | UsableItemsDetails) => any, componentName?: string) => {
    const columns = [
        {
            name: 'Name',
            selector: (row: { item_name: string; }) => row.item_name,
            cell: (row: any) => <span className='m-auto'><ItemNameColorationButton item={row} on_click={clickAction} /></span>
        },
        {
            name: 'Type',
            selector: (row: { type: string; }) => row.type,
            sortable: true,
        },
        {
            name: 'Attack',
            selector: (row: { attack: number; }) => row.attack,
            sortable: true,
            format: (row: any) => formatNumber(row.attack)
        },
        {
            name: 'AC',
            selector: (row: { ac: number; }) => row.ac,
            sortable: true,
            format: (row: any) => formatNumber(row.ac)
        },
        {
            name: 'Holy Stacks',
            selector: (row: { holy_stacks: number; has_holy_stacks_applied: number; }) => row.holy_stacks,
            sortable: true,
            format: (row: any) => row.has_holy_stacks_applied + '/' + row.holy_stacks
        },
    ];

    if (typeof componentName !== 'undefined') {
        if (componentName === 'equipped') {
            columns.push({
                name: 'Position',
                selector: (row: any) => '',
                cell: (row: any) => row.position,
            });
        }
    }

    if (typeof component !== 'undefined') {
        columns.push({
            name: 'Actions',
            selector: (row: any) => '',
            cell: (row: any) => component.actions(row)
        });
    }

    return columns;
}

/**
 * Build A limited set of columns.
 *
 * @param component
 * @param onClick
 */
export const buildLimitedColumns = (component?: ActionsInterface, onClick?: (item?: InventoryDetails | UsableItemsDetails) => any, usableItem?: boolean) => {
        const columns = [
            {
                name: 'Name',
                selector: (row: { item_name: string; }) => row.item_name,
                cell: (row: any) => <ItemNameColorationButton item={row} on_click={onClick}/>
            },
            {
                name: 'Description',
                selector: (row: { description: string; }) => row.description,
                cell: (row: any) => row.description
            },
        ];

        if (usableItem) {
            columns.push({
                name: 'Can Stack',
                selector: (row: any) => '',
                cell: (row: any) => row.can_stack ? 'Yes' : 'No'
            })
        }

        if (typeof component !== 'undefined') {
            columns.push({
                name: 'Actions',
                selector: (row: any) => '',
                cell: (row: any) => component.actions(row)
            });
        }

        return columns
}
