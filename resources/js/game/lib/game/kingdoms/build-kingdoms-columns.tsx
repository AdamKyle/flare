import KingdomDetails from "./kingdom-details";
import React from "react";
import {formatNumber} from "../format-number";

export const buildKingdomsColumns = (onClick: (kingdom: KingdomDetails) => void) => {
    return [
        {
            name: 'Name',
            selector: (row: KingdomDetails) => row.name,
            cell: (row: any) => <button onClick={() => onClick(row)} className='text-blue-500 dark:text-blue-400 hover:text-blue-600 dark:hover:text-blue-500'>{row.name}</button>
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
