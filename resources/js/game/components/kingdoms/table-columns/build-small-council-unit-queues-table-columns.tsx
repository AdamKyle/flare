import React, { Fragment } from "react";
import BuildingsToUpgradeSection from "../capital-city/buildings-to-upgrade-section";
import PrimaryOutlineButton from "../../ui/buttons/primary-outline-button";
import DangerOutlineButton from "../../ui/buttons/danger-outline-button";
import {
    addToQueue,
    removeFromQueue,
} from "../capital-city/helpers/queue_management";
import BuildingQueuesTable from "../capital-city/building-queues-table";
import BuildingDetails from "../buildings/deffinitions/building-details";
import TimerProgressBar from "../../ui/progress-bars/timer-progress-bar";
import DangerButton from "../../ui/buttons/danger-button";
import UnitQueuesTable from "../capital-city/unit-queues-table";
import { formatNumber } from "../../../lib/game/format-number";

/**
 *
 * @param component
 */
export const buildSmallCouncilUnitQueuesTableColumns = (
    component: UnitQueuesTable,
) => {
    return [
        {
            name: "Kingdom Name",
            selector: (row: any) => row.kingdom_name,
            cell: (row: any) => <span>{row.kingdom_name}</span>,
        },
        {
            name: "Unit Name",
            selector: (row: any) => row.unit_name,
            cell: (row: any) => <span>{row.unit_name}</span>,
        },
        {
            name: "Request Status",
            selector: (row: any) => row.status,
            cell: (row: any) => <span>{row.status}</span>,
        },
        {
            name: "Unit Status",
            selector: (row: any) => row.secondary_status,
            cell: (row: any) => (
                <span>
                    {row.secondary_status === null
                        ? "N/A"
                        : row.secondary_status}
                </span>
            ),
        },
        {
            name: "Amount",
            cell: (row: any) => <span>{formatNumber(row.amount)}</span>,
        },
        {
            name: "Time Left",
            minWidth: "300px",
            cell: (row: any) => (
                <Fragment>
                    <div className="w-full mt-2">
                        <TimerProgressBar
                            time_remaining={row.time_left_seconds}
                            time_out_label={getTimerTitle(row)}
                            useSmallTimer={component.state.view_port < 800}
                        />
                        {row.time_left_seconds > 0 ? (
                            <div className="mb-2 mt-4">
                                <button
                                    className={
                                        "hover:text-red-500 text-red-700 dark:text-red-500 dark:hover:text-red-400 " +
                                        "disabled:text-red-400 dark:disabled:bg-red-400 disabled:line-through " +
                                        "focus:outline-none focus-visible:ring-2 focus-visible:ring-red-200 dark:focus-visible:ring-white " +
                                        "focus-visible:ring-opacity-75"
                                    }
                                    onClick={() =>
                                        console.log("cancel the travel")
                                    }
                                >
                                    {getCancelTimerTitle(row)}
                                </button>
                            </div>
                        ) : null}
                    </div>
                </Fragment>
            ),
        },
    ];
};

const getTimerTitle = (row: any): string => {
    if (row.status === "traveling") {
        return "Traveling";
    }

    if (row.secondary_status === "recruiting") {
        return "Recruiting";
    }

    if (row.secondary_status === "requesting") {
        return "Requesting";
    }

    return "UNKNOWN";
};

const getCancelTimerTitle = (row: any): string => {
    if (row.status === "traveling") {
        return "Cancel traveling";
    }

    if (row.secondary_status === "recruiting") {
        return "Cancel recruiting";
    }

    if (row.secondary_status === "requesting") {
        return "Cancel requesting";
    }

    return "UNKNOWN";
};
