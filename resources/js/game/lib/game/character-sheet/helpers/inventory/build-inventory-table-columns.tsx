import React from "react";
import ItemNameColorationButton from "../../../../../components/items/item-name/item-name-coloration-button";
import { formatNumber } from "../../../format-number";
import ActionsInterface from "./actions-interface";
import InventoryDetails from "../../types/inventory/inventory-details";
import UsableItemsDetails from "../../types/inventory/usable-items-details";
import GemBagDetails from "../../types/inventory/gem-bag-details";
import {GemBagTable} from "../../../../../sections/character-sheet/components/tabs/inventory-tabs/gem-bag-table";
import PrimaryLinkButton from "../../../../../components/ui/buttons/primary-link-button";

/**
 * Build Inventory Table Columns
 *
 * @param component
 * @param clickAction
 * @param componentName
 * @constructor
 */
export const BuildInventoryTableColumns = (component?: ActionsInterface, clickAction?: (item?: InventoryDetails | UsableItemsDetails) => any, manageSkills?: (slotId: number, itemSkills: any[]|[], itemSkillProgressions: any[]) => void, componentName?: string) => {
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

    if (typeof manageSkills !== 'undefined') {
        columns.push({
            name: 'Item Skills',
            selector: (row: any) => row.item_skill,
            cell: (row: any) => <span>
                {
                    row.item_skill_progressions.length > 0 ?
                        <PrimaryLinkButton button_label={'Manage Skills'} on_click={() => manageSkills(row.slot_id, row.item_skills, row.item_skill_progressions)} />
                    : 'N/A'
                }
            </span>
        });
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

/**
 * build gem table columns.
 *
 * @param component
 * @param onClick
 */
export const buildGemColumns = (component: GemBagTable, onClick: (gemSlot: GemBagDetails) => void) => {
    const columns = [
        {
            name: 'Name',
            selector: (row: GemBagDetails) => row.name,
            cell: (row: GemBagDetails) => <button className='text-lime-600 dark:text-lime-500' onClick={() => onClick(row)}>{row.name}</button>
        },
        {
            name: 'Tier',
            selector: (row: GemBagDetails) => row.tier,
            cell: (row: GemBagDetails) => row.tier
        },
        {
            name: 'Amount',
            selector: (row: GemBagDetails) => row.amount,
            cell: (row: GemBagDetails) => row.amount
        },
        {
            name: 'Atoned To',
            selector: (row: GemBagDetails) => row.element_atoned_to,
            cell: (row: GemBagDetails) => row.element_atoned_to
        },
        {
            name: 'Atoned Amount',
            selector: (row: GemBagDetails) => row.element_atoned_to_amount,
            cell: (row: GemBagDetails) => (row.element_atoned_to_amount * 100).toFixed(2) + '%'
        },
        {
            name: 'Strong Against',
            selector: (row: GemBagDetails) => row.strong_against,
            cell: (row: GemBagDetails) => row.strong_against
        },
        {
            name: 'Weak Against',
            selector: (row: GemBagDetails) => row.weak_against,
            cell: (row: GemBagDetails) => row.weak_against
        }
    ];

    if (typeof component !== 'undefined') {
        columns.push({
            name: 'Actions',
            selector: (row: any) => '',
            cell: (row: any) => component.gemActions(row)
        });
    }

    return columns
}
