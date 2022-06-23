import React from "react";
import {formatNumber} from "../format-number";
import BuildingDetails from "./building-details";
import clsx from "clsx";
import UnitDetails from "./unit-details";

/**
 * Build the columns for the units table.
 *
 * @param onClick
 */
export const BuildUnitsColumns = (onClick: (units: UnitDetails) => void) => {
    return [
        {
            name: 'Name',
            selector: (row: UnitDetails) => row.name,
            cell: (row: UnitDetails) =>
                <button onClick={() => onClick(row)}
                        className={clsx({
                            'text-blue-500 dark:text-blue-400 hover:text-blue-600 dark:hover:text-blue-500': true,
                            'text-white underline': false
                        })}
                >
                    {row.name}
                </button>
        },
        {
            name: 'Recruited From',
            selector: (row: UnitDetails) => row.recruited_from.name,
        },
        {
            name: 'Attack',
            selector: (row: UnitDetails) => row.attack,
            cell: (row: UnitDetails) => <span>{row.attack}</span>
        },
        {
            name: 'Defence',
            selector: (row: UnitDetails) => row.defence,
            cell: (row: UnitDetails) => formatNumber(row.defence),
        },
    ];
}
