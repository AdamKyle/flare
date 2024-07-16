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
import { capitalize } from "lodash";

/**
 *
 * @param component
 */
export const buildSmallCouncilBuildingsQueuesTableColumns = (
    component: BuildingQueuesTable,
) => {
    return [
        {
            name: "Kingdom Name",
            selector: (row: any) => row.kingdom_name,
            cell: (row: any) => <span>{row.kingdom_name}</span>,
        },
        {
            name: "Building Name",
            selector: (row: any) => row.building_name,
            cell: (row: any) => <span>{row.building_name}</span>,
        },
        {
            name: "Request Status",
            selector: (row: any) => row.status,
            cell: (row: any) => <span>{capitalize(row.status)}</span>,
        },
        {
            name: "Building Status",
            selector: (row: any) =>
                row.secondary_status !== null
                    ? capitalize(row.secondary_status)
                    : "N/A",
            cell: (row: any) => (
                <span>
                    {row.secondary_status !== null
                        ? capitalize(row.secondary_status)
                        : "N/A"}
                </span>
            ),
        },
        {
            name: "Time Left",
            minWidth: "300px",
            cell: (row: any) => (
                <Fragment>
                    <div className="w-full mt-2">
                        {row.secondary_status === "finished" ||
                        row.secondary_status === "rejected"  ||
                        row.secondary_status === "cancelled" ? (
                            <p>
                                No time remaining. Log will be generated when
                                other requests for this kingdom are done.
                            </p>
                        ) : (
                            <>
                                <TimerProgressBar
                                    time_remaining={row.time_left_seconds}
                                    time_out_label={buildTimerTitle(row)}
                                    useSmallTimer={
                                        component.state.view_port < 800
                                    }
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
                                                component.manageCancelModal(
                                                    row.building_id,
                                                )
                                            }
                                            disabled={
                                                row.secondary_status === "cancelled" ||
                                                row.secondary_status === "requesting" ||
                                                row.secondary_status === "finished" ||
                                                row.secondary_status === "rejected"
                                            }
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                ) : null}
                            </>
                        )}
                    </div>
                </Fragment>
            ),
        },
    ];
};

const buildTimerTitle = (row: any): string => {
    if (row.status === "traveling") {
        return "Traveling";
    }

    if (row.secondary_status === "repairing") {
        return "Repairing";
    }

    if (row.secondary_status === "building") {
        return "Upgrading";
    }

    if (row.secondary_status === "requesting") {
        return "Requesting";
    }

    return "UNKNOWN";
};
