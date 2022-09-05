import React from "react";
import KingdomLogDetails from "./kingdom-log-details";
import DangerButton from "../../../components/ui/buttons/danger-button";

export const buildLogsColumns = (onClick: (log: KingdomLogDetails) => void, deleteLog: (log: KingdomLogDetails) => void) => {
    return [
        {
            name: 'Title',
            selector: (row: KingdomLogDetails) => row.status,
            cell: (row: any) => <button className='text-blue-500 dark:text-blue-400 hover:text-blue-600 dark:hover:text-blue-500' onClick={() => onClick(row)}>
                {row.status}
            </button>
        },
        {
            name: 'Kingdom Attacked',
            selector: (row: KingdomLogDetails) => row.to_kingdom_name
        },
        {
            name: 'Attacked From',
            selector: (row: KingdomLogDetails) => row.from_kingdom_name === null ? 'N/A' : row.from_kingdom_name
        },
        {
            name: 'Created At',
            selector: (row: KingdomLogDetails) => row.created_at,
        },
        {
            name: 'Actions',
            selector: (row: KingdomLogDetails) => row.status,
            cell: (row: any) => <DangerButton button_label={'Delete Log'} on_click={() => deleteLog(row)} />
        },
    ];
}
