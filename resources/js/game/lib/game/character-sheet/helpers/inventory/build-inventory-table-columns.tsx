import React, {Component} from "react";
import ItemNameColoration from "../../../../../components/ui/item-name-coloration";
import { formatNumber } from "../../../format-number";
import ActionsInterface from "./actions-interface";

export const BuildInventoryTableColumns = (component?: ActionsInterface) => {
    const columns = [
        {
            name: 'Name',
            selector: (row: { item_name: string; }) => row.item_name,
            sortable: true,
            cell: (row: any) => <ItemNameColoration item={row} />
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
    ];

    if (typeof component !== 'undefined') {
        columns.push({
            name: 'Actions',
            selector: (row: any) => '',
            sortable: true,
            cell: (row: any) => component.actions(row)
        });
    }

    return columns;
}

export const buildLimitedColumns = (component?: ActionsInterface) => {
        const columns = [
            {
                name: 'Name',
                selector: (row: { item_name: string; }) => row.item_name,
                sortable: true,
                cell: (row: any) => <ItemNameColoration item={row} />
            },
            {
                name: 'Description',
                selector: (row: { description: string; }) => row.description,
                sortable: true,
                cell: (row: any) => row.description
            },
        ];

        if (typeof component !== 'undefined') {
            columns.push({
                name: 'Actions',
                selector: (row: any) => '',
                sortable: true,
                cell: (row: any) => component.actions(row)
            });
        }

        return columns
}
