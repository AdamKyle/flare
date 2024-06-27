import React, { Fragment } from "react";
import FetchUpgradableKingdomsAjax from "../ajax/fetch-upgradable-kingdoms-ajax";
import { serviceContainer } from "../../../lib/containers/core-container";
import LoadingProgressBar from "../../ui/progress-bars/loading-progress-bar";
import DangerAlert from "../../ui/alerts/simple-alerts/danger-alert";
import PrimaryOutlineButton from "../../ui/buttons/primary-outline-button";
import SuccessOutlineButton from "../../ui/buttons/success-outline-button";
import OrangeOutlineButton from "../../ui/buttons/orange-outline-button";
import DangerOutlineButton from "../../ui/buttons/danger-outline-button";
import Table from "../../ui/data-tables/table";
import { buildSmallCouncilBuildingsTableColumns } from "../table-columns/build-small-council-buildings-table-columns";
import SendRequestConfirmationModal from "./modals/send-request-confirmation-modal";
import {
    addAllBuildingsToQueue,
    removeAllFromQueue,
} from "./helpers/queue_management";
import {
    sortByBuildingLevel,
    sortByBuildingName,
    sortByKingdomName,
} from "./helpers/sort_helpers";
import {
    Building,
    CompressedData,
    Kingdom,
} from "./deffinitions/kingdom_building_data";
import CapitalCityBuildingUpgradeRepairTableEventDefinition from "../event-listeners/capital-city-building-upgrade-repair-table-event-definition";
import CapitalCityBuildingUpgradeRepairTableEvent from "../event-listeners/capital-city-building-upgrade-repair-table-event";
import clsx from "clsx";

enum SortType {
    KINGDOM_NAME = "kingdom-name",
    BUILDING_NAME = "building-name",
    BUILDING_LEVEL = "building-level",
}

export default class BuildingsToUpgradeSection extends React.Component<
    any,
    any
> {
    private fetchUpgradableKingdomsAjax: FetchUpgradableKingdomsAjax;

    private updateBuildingTable: CapitalCityBuildingUpgradeRepairTableEventDefinition;

    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            success_message: null,
            error_message: null,
            building_data: [],
            table_data: [],
            sort_type: null,
            order_type: null,
            upgrade_queue: [],
            show_review_modal: false,
            kingdom_search: "",
        };

        this.fetchUpgradableKingdomsAjax = serviceContainer().fetch(
            FetchUpgradableKingdomsAjax,
        );

        this.updateBuildingTable =
            serviceContainer().fetch<CapitalCityBuildingUpgradeRepairTableEventDefinition>(
                CapitalCityBuildingUpgradeRepairTableEvent,
            );

        this.updateBuildingTable.initialize(this, this.props.user_id);

        this.updateBuildingTable.register();
    }

    componentDidMount() {
        this.fetchUpgradableKingdomsAjax.fetchDetails(
            this,
            this.props.kingdom.character_id,
            this.props.kingdom.id,
        );

        this.updateBuildingTable.listen();
    }

    resetTableForms() {
        this.setState({
            sort_type: null,
            order_type: null,
            upgrade_queue: [],
            kingdom_search: "",
        });
    }

    compressArray(sortedData: Kingdom[], returnData: boolean) {
        const compressed: CompressedData[] = [];

        sortedData.forEach((kingdom) => {
            kingdom.buildings.forEach((building: Building) => {
                compressed.push({
                    kingdom_name: kingdom.kingdom_name,
                    building_name: building.name,
                    building_id: building.id,
                    kingdom_id: kingdom.kingdom_id,
                    level: building.level,
                    max_level: building.max_level,
                    current_durability: building.current_durability,
                    max_durability: building.max_durability,
                });
            });
        });

        if (returnData) {
            return compressed;
        }

        this.setState({
            table_data: compressed,
        });
    }

    showRemoveButton(buildingId: number): boolean {
        return (
            this.state.upgrade_queue.filter((queue: any) => {
                return queue.buildingIds.includes(buildingId);
            }).length > 0
        );
    }

    sortTable(type: SortType, order: string) {
        let sortedData = JSON.parse(JSON.stringify(this.state.building_data));

        sortedData = this.compressArray(sortedData, true);

        let orderType =
            this.state.order_type === null
                ? order
                : this.state.order_type === "asc"
                  ? "desc"
                  : "asc";

        switch (type) {
            case SortType.KINGDOM_NAME:
                sortedData = sortByKingdomName(sortedData, orderType);
                break;
            case SortType.BUILDING_NAME:
                sortedData = sortByBuildingName(sortedData, orderType);
                break;
            case SortType.BUILDING_LEVEL:
                sortedData = sortByBuildingLevel(sortedData, orderType);
                break;
            default:
                sortedData = sortByKingdomName(sortedData, orderType);
        }

        this.setState({
            table_data: sortedData,
            order_type: orderType,
            sort_type: type,
        });
    }

    resetSort() {
        this.setState(
            {
                sort_type: null,
                order_type: null,
                kingdom_search: "",
            },
            () => {
                this.compressArray(this.state.building_data, false);
            },
        );
    }

    manageReviewModal() {
        this.setState({
            show_review_modal: !this.state.show_review_modal,
        });
    }

    filterKingdomsTable(event: React.ChangeEvent<HTMLInputElement>): void {
        const value = event.target.value;

        let sortedData = JSON.parse(JSON.stringify(this.state.building_data));

        sortedData = this.compressArray(sortedData, true);

        if (value === "") {
            this.setState({
                sort_type: null,
                order_type: null,
                upgrade_queue: [],
                kingdom_search: "",
                table_data: sortedData,
            });

            return;
        }

        sortedData = sortedData.filter((tableData: any) => {
            return (
                tableData.kingdom_name.includes(value) ||
                tableData.building_name.includes(value)
            );
        });

        this.setState({
            sort_type: null,
            order_type: null,
            upgrade_queue: [],
            kingdom_search: value,
            table_data: sortedData,
        });
    }

    render() {
        if (this.state.loading) {
            return <LoadingProgressBar />;
        }

        return (
            <div>
                {this.state.error_message !== null ? (
                    <DangerAlert additional_css={"my-2"}>
                        {this.state.error_message}
                    </DangerAlert>
                ) : null}

                {this.state.success_message !== null ? (
                    <DangerAlert additional_css={"my-2"}>
                        {this.state.success_message}
                    </DangerAlert>
                ) : null}

                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4"></div>

                <h4>
                    {this.props.repair ? "Repair orders" : "Upgrade Orders"}
                </h4>

                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4"></div>

                <div className="flex items-center flex-wrap">
                    <PrimaryOutlineButton
                        button_label={
                            "Queue All Buildings (" +
                            (this.props.repair ? "Repair" : "Upgrade") +
                            ")"
                        }
                        on_click={() => {
                            addAllBuildingsToQueue(this);
                        }}
                        additional_css={"flex-1 py-2 px-4 min-w-[120px]"} // Set minimum width
                        disabled={
                            this.state.table_data.length <= 0 &&
                            this.state.kingdom_search === ""
                        }
                    />

                    {this.state.upgrade_queue.length > 0 ? (
                        <Fragment>
                            <SuccessOutlineButton
                                button_label={"Review/Send Orders"}
                                on_click={this.manageReviewModal.bind(this)}
                                additional_css={
                                    "flex-1 py-2 px-3 ml-2 min-w-[120px]"
                                } // Set minimum width
                                disabled={
                                    this.state.table_data.length <= 0 &&
                                    this.state.kingdom_search === ""
                                }
                            />
                            <DangerOutlineButton
                                button_label={"Remove All From Queue"}
                                on_click={() => removeAllFromQueue(this)}
                                additional_css={
                                    "flex-1 py-2 px-3 ml-2 min-w-[120px]"
                                } // Set minimum width
                                disabled={
                                    this.state.table_data.length <= 0 &&
                                    this.state.kingdom_search === ""
                                }
                            />
                        </Fragment>
                    ) : null}
                </div>

                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4"></div>

                <div
                    className={clsx(
                        "flex space-x-2 items-center flex-wrap my-2",
                    )}
                >
                    <PrimaryOutlineButton
                        button_label={"Sort by Kingdom Name"}
                        on_click={() =>
                            this.sortTable(SortType.KINGDOM_NAME, "asc")
                        }
                        additional_css={"py-2 px-3 flex-shrink-0 min-w-[120px]"} // Set minimum width
                        disabled={
                            this.state.table_data.length <= 0 &&
                            this.state.kingdom_search === ""
                        }
                    />
                    <SuccessOutlineButton
                        button_label={"Sort by Building Name"}
                        on_click={() =>
                            this.sortTable(SortType.BUILDING_NAME, "asc")
                        }
                        additional_css={"py-2 px-3 flex-shrink-0 min-w-[120px]"} // Set minimum width
                        disabled={
                            this.state.table_data.length <= 0 &&
                            this.state.kingdom_search === ""
                        }
                    />
                    <OrangeOutlineButton
                        button_label={"Sort by Building Level"}
                        on_click={() =>
                            this.sortTable(SortType.BUILDING_LEVEL, "asc")
                        }
                        additional_css={"py-2 px-3 flex-shrink-0 min-w-[120px]"} // Set minimum width
                        disabled={
                            this.state.table_data.length <= 0 &&
                            this.state.kingdom_search === ""
                        }
                    />
                    <DangerOutlineButton
                        button_label={"Reset Filters/Search"}
                        on_click={() => this.resetSort()}
                        additional_css={"py-2 px-3 flex-shrink-0 min-w-[120px]"} // Set minimum width
                        disabled={
                            this.state.table_data.length <= 0 &&
                            this.state.kingdom_search === ""
                        }
                    />
                </div>

                <div className="flex flex-col md:flex-row items-center space-y-2 md:space-y-0 md:space-x-4 my-4">
                    <label className="block text-gray-700 w-32 md:w-20 dark:text-gray-100">
                        Search
                    </label>
                    <input
                        type="text"
                        value={this.state.kingdom_search}
                        className="block border border-gray-500 dark:border-gray-200 rounded p-2 w-full md:w-80 bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-400 dark:focus:ring-gray-600"
                        onChange={this.filterKingdomsTable.bind(this)}
                        placeholder="Enter Kingdom/Building Name"
                    />
                </div>

                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4"></div>

                <Table
                    data={this.state.table_data}
                    columns={buildSmallCouncilBuildingsTableColumns(this)}
                    dark_table={false}
                />

                {this.state.show_review_modal ? (
                    <SendRequestConfirmationModal
                        is_open={this.state.show_review_modal}
                        manage_modal={this.manageReviewModal.bind(this)}
                        character_id={this.props.kingdom.character_id}
                        kingdom_id={this.props.kingdom.id}
                        params={this.state.upgrade_queue}
                        repair={this.props.repair}
                        reset_table_forms={this.resetTableForms.bind(this)}
                    />
                ) : null}
            </div>
        );
    }
}
