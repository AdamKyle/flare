import KingdomDetails from "./kingdom-details";
import React from "react";
import {formatNumber} from "../format-number";
import clsx from "clsx";

export const buildKingdomsColumns = (onClick: (kingdom: KingdomDetails) => void) => {
    return [
        {
            name: 'Name',
            selector: (row: KingdomDetails) => row.name,
            cell: (row: any) => <button onClick={() => onClick(row)} className={clsx({
                'text-blue-500 dark:text-blue-400 hover:text-blue-600 dark:hover:text-blue-500': row.is_protected,
                'text-red-500 dark:text-red-400 hover:text-red-600 dark:hover:text-red-500': row.is_under_attack,
                'text-white underline': row.is_protected || row.is_under_attack,
            })}>{row.name} {row.is_protected ? ' (Protected) ' : ''}</button>
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
