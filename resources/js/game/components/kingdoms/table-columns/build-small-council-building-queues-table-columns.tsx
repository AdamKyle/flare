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
            cell: (row: any) => <span>{row.status}</span>,
        },
        {
            name: "Building Status",
            selector: (row: any) => row.secondary_status,
            cell: (row: any) => <span>{row.secondary_status}</span>,
        },
        {
            name: "Actions",
            cell: (row: any) => (
                <Fragment>
                    <DangerButton
                        button_label={"Cancel Action"}
                        on_click={() => console.log("Cancel Action")}
                        disabled={
                            row.status !== "progressing" &&
                            row.secondary_status === "rejected"
                        }
                    />
                </Fragment>
            ),
        },
        {
            name: "Time Left",
            minWidth: "300px",
            cell: (row: any) => (
                <Fragment>
                    <div className="w-full mt-2">
                        <TimerProgressBar
                            time_remaining={row.time_left_seconds}
                            time_out_label={"Traveling"}
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
                                    Cancel
                                </button>
                            </div>
                        ) : null}
                    </div>
                </Fragment>
            ),
        },
    ];
};

const renderMessages = (messages: String[] | []) => {
    if (messages === null) {
        return "N/A";
    }

    if (messages.length <= 0) {
        return "N/A";
    }

    const messageLis = messages.map((message) => {
        return <li className={"ml-2"}>{message}</li>;
    });

    return <ul className={"list-disc"}>{messageLis}</ul>;
};
