import React from "react";
import {formatNumber} from "../format-number";
import BuildingDetails from "./building-details";
import clsx from "clsx";

/**
 * Build the columns for the buildings table.
 *
 * @param onClick
 */
export const buildBuildingsColumns = (onClick: (building: BuildingDetails) => void) => {
    return [
        {
            name: 'Name',
            selector: (row: BuildingDetails) => row.name,
            cell: (row: BuildingDetails) =>
                <button onClick={() => onClick(row)}
                        className={clsx({
                            'text-blue-500 dark:text-blue-400 hover:text-blue-600 dark:hover:text-blue-500': !row.is_locked,
                            'text-white underline': row.is_locked
                        })}
                >
                    {row.name}
                </button>
        },
        {
            name: 'Level',
            selector: (row: BuildingDetails) => row.level,
            cell: (row: BuildingDetails) => <span>{row.level}/{row.max_level}</span>
        },
        {
            name: 'Defence',
            selector: (row: BuildingDetails) => row.current_defence,
            cell: (row: BuildingDetails) => formatNumber(row.current_defence),
        },
        {
            name: 'Durability',
            selector: (row: BuildingDetails) => row.current_durability,
            cell: (row: BuildingDetails) => <span>{row.current_durability}/{row.max_durability}</span>
        },
    ];
}
