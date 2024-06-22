import React from "react";
import BuildingsToUpgradeSection from "../capital-city/buildings-to-upgrade-section";
import PrimaryOutlineButton from "../../ui/buttons/primary-outline-button";
import DangerOutlineButton from "../../ui/buttons/danger-outline-button";
import {
    addToQueue,
    removeFromQueue,
} from "../capital-city/helpers/queue_management";

/**
 *
 * @param component
 */
export const buildSmallCouncilBuildingsTableColumns = (
    component: BuildingsToUpgradeSection,
) => {
    const columns = [
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
            name: "Actions",
            selector: (row: any) => row.building_id,
            cell: (row: any) => (
                <span>
                    {component.showRemoveButton(row.building_id) ? (
                        <DangerOutlineButton
                            button_label={
                                "Remove from" +
                                (component.props.repair ? " repair " : " ") +
                                "queue"
                            }
                            on_click={() =>
                                removeFromQueue(
                                    component,
                                    row.kingdom_id,
                                    row.building_id,
                                )
                            }
                        />
                    ) : (
                        <PrimaryOutlineButton
                            button_label={
                                "Queue for" +
                                (component.props.repair ? " repair " : " ") +
                                "orders"
                            }
                            on_click={() =>
                                addToQueue(
                                    component,
                                    row.kingdom_id,
                                    row.building_id,
                                )
                            }
                        />
                    )}
                </span>
            ),
        },
    ];

    if (component.props.repair) {
        columns.splice(1, 0, {
            name: "Building Curr. Durability",
            selector: (row: any) => row.current_durability,
            cell: (row: any) => row.current_durability,
        });

        columns.splice(2, 0, {
            name: "Building Max Durability",
            selector: (row: any) => row.max_durability,
            cell: (row: any) => row.max_durability,
        });
    } else {
        columns.splice(1, 0, {
            name: "Building Level",
            selector: (row: any) => row.level,
            cell: (row: any) => row.level,
        });

        columns.splice(2, 0, {
            name: "Building Max Level",
            selector: (row: any) => row.max_level,
            cell: (row: any) => <span>{row.max_level}</span>,
        });
    }

    return columns;
};
