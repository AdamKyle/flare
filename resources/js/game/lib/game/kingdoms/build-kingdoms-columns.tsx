import KingdomDetails from "./kingdom-details";
import React from "react";
import {formatNumber} from "../format-number";
import clsx from "clsx";
import UnitMovementDetails from "./unit-movement-details";

export const buildKingdomsColumns = (onClick: (kingdom: KingdomDetails) => void) => {
    return [
        {
            name: 'Name',
            selector: (row: KingdomDetails) => row.name,
            cell: (row: any) => <button className='text-blue-500 dark:text-blue-400 hover:text-blue-600 dark:hover:text-blue-500' onClick={() => onClick(row)}>
                {iconsToShow(row)} {row.name} {row.is_protected ? ' (Protected) ' : ''}
            </button>
        },
        {
            name: 'X Position',
            selector: (row: KingdomDetails) => row.x_position
        },
        {
            name: 'Y Position',
            selector: (row: KingdomDetails) => row.y_position
        },
        {
            name: 'Current Morale',
            selector: (row: KingdomDetails) => row.current_morale,
            cell: (row: any) => (row.current_morale * 100).toFixed(2) + '%'
        },
        {
            name: 'Treasury',
            selector: (row: KingdomDetails) => row.treasury,
            cell: (row: any) => formatNumber(row.treasury)
        },
    ];
}

const iconsToShow = (kingdom: KingdomDetails) => {
    const icons = [];

    if (kingdom.is_protected) {
        icons.push(
            <i className='ra ra-heavy-shield text-blue-500 dark:text-blue-400'></i>
        )
    }

    if (kingdom.is_under_attack) {
        icons.push(
            <i className='ra ra-axe text-red-500 dark:text-red-400'></i>
        )
    }

    const anyMoving = kingdom.unitsInMovement.filter((unitMovement: UnitMovementDetails) => {
        const anyMoving = unitMovement.is_returning || unitMovement.is_moving || unitMovement.is_recalled || unitMovement.is_attacking;
        const fromThisKingdom = kingdom.name === unitMovement.from_kingdom_name;

        return anyMoving && fromThisKingdom;
    });

    if (anyMoving.length > 0) {
        if (anyMoving[0].is_attacking) {
            icons.push(
                <i className='ra ra-axe text-red-500 dark:text-red-400'></i>
            );
        }

        if (anyMoving[0].is_returning || anyMoving[0].is_recalled) {
            icons.push(
                <i className="fas fa-exchange-alt text-orange-500 dark:text-orange-300"></i>
            );
        } else {
            icons.push(
                <i className='ra ra-trail text-green-500 dark:text-green-400'></i>
            );
        }

    }

    return icons;
}
