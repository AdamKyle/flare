import React from "react";
import FetchKingdomsForSelectionAjax from "../ajax/fetch-kingdoms-for-selection-ajax";
import { serviceContainer } from "../../../lib/containers/core-container";
import LoadingProgressBar from "../../ui/progress-bars/loading-progress-bar";
import debounce from "lodash/debounce";
import { UnitTypes } from "../deffinitions/unit-types";

export default class UnitRecruitment extends React.Component<any, any> {
    private fetchKingdomsForSelectionAjax: FetchKingdomsForSelectionAjax;

    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            success_message: null,
            error_message: null,
            unit_recruitment_data: [],
            filtered_unit_recruitment_data: [],
            open_kingdom_ids: new Set(),
            search_term: "",
            unit_queue: [],
            bulk_input_values: {}, // New state to track bulk input values per kingdom
        };

        this.fetchKingdomsForSelectionAjax = serviceContainer().fetch(
            FetchKingdomsForSelectionAjax,
        );
    }

    componentDidMount() {
        this.fetchKingdomsForSelectionAjax.fetchDetails(
            this,
            this.props.kingdom.character_id,
            this.props.kingdom.id,
        );
    }

    componentDidUpdate(prevProps: any, prevState: any) {
        if (
            prevState.unit_recruitment_data !== this.state.unit_recruitment_data
        ) {
            this.updateFilteredUnitData();
        }
    }

    updateFilteredUnitData() {
        const searchTerm = this.state.search_term.toLowerCase();

        const openKingdomIds = new Set<number>();

        let filteredData = this.state.kingdoms_for_selection
            .map((kingdom: any) => {
                const kingdomNameMatches =
                    kingdom.name.toLowerCase() === searchTerm;
                const mapNameMatches = kingdom.game_map_name
                    .toLowerCase()
                    .includes(searchTerm);

                return kingdom;
            })
            .filter((kingdom: any) => {
                return (
                    kingdom.name.toLowerCase() === searchTerm ||
                    kingdom.game_map_name.toLowerCase().includes(searchTerm)
                );
            });

        const unitTypes = Object.values(UnitTypes).map((v: string) =>
            v.toLowerCase(),
        );

        console.log(
            unitTypes,
            searchTerm,
            unitTypes.includes(searchTerm),
            filteredData,
            filteredData.length,
            unitTypes.includes(searchTerm) && filteredData.length === 0, // Fixed typo here
        );

        if (unitTypes.includes(searchTerm) && filteredData.length === 0) {
            // Fixed typo here
            console.log("Here?");
            filteredData = this.state.kingdoms_for_selection;

            this.state.kingdoms_for_selection.forEach((kingdom: any) => {
                openKingdomIds.add(kingdom.id);
            });
        }

        console.log(filteredData, openKingdomIds);

        this.setState({
            filtered_unit_recruitment_data: filteredData,
            open_kingdom_ids: openKingdomIds,
        });
    }

    handleSearchChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        this.setState({ search_term: event.target.value }, () => {
            this.debouncedUpdateFilteredData();
        });
    };

    debouncedUpdateFilteredData = debounce(() => {
        this.updateFilteredUnitData();
    }, 300);

    resetFilters = () => {
        this.setState(
            {
                search_term: "",
            },
            () => {
                this.updateFilteredUnitData();
            },
        );
    };

    resetQueue = () => {
        this.setState({
            unit_queue: [],
            bulk_input_values: {}, // Clear bulk input values
        });
    };

    sendOrders = () => {
        console.log("Sending orders:", this.state.unit_queue);
    };

    handleUnitAmountChange(
        kingdomId: number,
        unitName: string,
        amount: number | string,
    ) {
        const updatedQueue = [...this.state.unit_queue];

        let kingdomQueue = updatedQueue.find(
            (item) => item.kingdom_id === kingdomId,
        );

        if (!kingdomQueue) {
            kingdomQueue = {
                kingdom_id: kingdomId,
                unit_requests: [],
            };
            updatedQueue.push(kingdomQueue);
        }

        const unitRequest = kingdomQueue.unit_requests.find(
            (request: any) => request.unit_name === unitName,
        );

        if (unitRequest) {
            unitRequest.unit_amount = amount;
        } else {
            kingdomQueue.unit_requests.push({
                unit_name: unitName,
                unit_amount: amount,
            });
        }

        this.setState({ unit_queue: updatedQueue });
    }

    handleBulkAmountChange = (
        event: React.ChangeEvent<HTMLInputElement>,
        kingdomId: number,
    ) => {
        const amount = parseInt(event.target.value, 10) || "";
        this.setState(
            (prevState: any) => ({
                bulk_input_values: {
                    ...prevState.bulk_input_values,
                    [kingdomId]: amount,
                },
            }),
            () => {
                this.updateBulkAmounts(kingdomId, amount);
            },
        );
    };

    updateBulkAmounts(kingdomId: number, bulkAmount: number | string) {
        const { filtered_unit_recruitment_data } = this.state;

        filtered_unit_recruitment_data.forEach((kingdom: any) => {
            if (kingdom.id === kingdomId) {
                Object.values(UnitTypes).forEach((unitType: string) => {
                    this.handleUnitAmountChange(
                        kingdom.id,
                        unitType,
                        bulkAmount,
                    );
                });
            }
        });
    }

    getKingdomQueueSummary(kingdomId: number) {
        const kingdomQueue = this.state.unit_queue.find(
            (item: any) => item.kingdom_id === kingdomId,
        );

        if (!kingdomQueue) {
            return null;
        }

        return kingdomQueue.unit_requests
            .map(
                (request: any) =>
                    `${request.unit_name} - ${request.unit_amount}`,
            )
            .join(", ");
    }

    getUnitAmount(kingdomId: number, unitName: string) {
        const kingdomQueue = this.state.unit_queue.find(
            (item: any) => item.kingdom_id === kingdomId,
        );

        if (!kingdomQueue) {
            return "";
        }

        const unitRequest = kingdomQueue.unit_requests.find(
            (request: any) => request.unit_name === unitName,
        );

        return unitRequest ? unitRequest.unit_amount : "";
    }

    fetchUnitsToShow() {
        if (Object.values(UnitTypes).includes(this.state.search_term)) {
            return Object.values(UnitTypes).filter((type: string) => {
                return type === this.state.search_term;
            });
        }

        return Object.values(UnitTypes);
    }

    render() {
        if (this.state.loading) {
            return <LoadingProgressBar />;
        }

        return (
            <div className="md:p-4">
                <input
                    type="text"
                    value={this.state.search_term}
                    onChange={this.handleSearchChange}
                    placeholder="Search by kingdom name, unit name, or map name"
                    className="w-full mb-4 px-4 py-2 border rounded text-gray-900 dark:text-white bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    aria-label="Search by kingdom name, unit name, or map name"
                />

                <div className="flex space-x-2 mt-4">
                    <button
                        className="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                        onClick={this.sendOrders}
                    >
                        Send Orders
                    </button>
                    <button
                        className="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600"
                        onClick={this.resetQueue}
                    >
                        Reset Queue
                    </button>
                    <button
                        className="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600"
                        onClick={this.resetFilters}
                    >
                        Reset Filters
                    </button>
                </div>

                <div className="mb-4">
                    {this.state.filtered_unit_recruitment_data.map(
                        (kingdom: any) => (
                            <div
                                key={kingdom.id}
                                className="bg-gray-100 dark:bg-gray-700 shadow-md rounded-lg overflow-hidden mb-4"
                            >
                                <div
                                    className="p-4 flex justify-between items-center cursor-pointer"
                                    onClick={() =>
                                        this.setState((prevState: any) => {
                                            const newOpenKingdomIds = new Set(
                                                prevState.open_kingdom_ids,
                                            );
                                            if (
                                                newOpenKingdomIds.has(
                                                    kingdom.id,
                                                )
                                            ) {
                                                newOpenKingdomIds.delete(
                                                    kingdom.id,
                                                );
                                            } else {
                                                newOpenKingdomIds.add(
                                                    kingdom.id,
                                                );
                                            }
                                            return {
                                                open_kingdom_ids:
                                                    newOpenKingdomIds,
                                            };
                                        })
                                    }
                                >
                                    <div>
                                        <div className="text-xl font-semibold">
                                            {kingdom.name}
                                        </div>
                                        <div className="text-sm text-gray-600 dark:text-gray-400">
                                            {kingdom.game_map_name}
                                        </div>
                                    </div>
                                    <div>
                                        <i
                                            className={`fas ${
                                                this.state.open_kingdom_ids.has(
                                                    kingdom.id,
                                                )
                                                    ? "fa-chevron-up"
                                                    : "fa-chevron-down"
                                            }`}
                                        ></i>
                                    </div>
                                </div>
                                {this.state.open_kingdom_ids.has(
                                    kingdom.id,
                                ) && (
                                    <div className="p-4">
                                        <div className="mb-4 text-gray-700 dark:text-gray-300">
                                            Units in Queue:{" "}
                                            {this.getKingdomQueueSummary(
                                                kingdom.id,
                                            )}
                                        </div>
                                        <input
                                            type="number"
                                            value={
                                                this.state.bulk_input_values[
                                                    kingdom.id
                                                ] || ""
                                            }
                                            onChange={(e) =>
                                                this.handleBulkAmountChange(
                                                    e,
                                                    kingdom.id,
                                                )
                                            }
                                            placeholder="Bulk amount"
                                            className="w-full mb-4 px-4 py-2 border rounded text-gray-900 dark:text-white bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        />

                                        {this.fetchUnitsToShow().map(
                                            (unitType: string) => (
                                                <div
                                                    key={unitType}
                                                    className="flex items-center mb-2"
                                                >
                                                    <span className="w-1/3 text-gray-700 dark:text-gray-300">
                                                        {unitType}
                                                    </span>
                                                    <input
                                                        type="number"
                                                        value={this.getUnitAmount(
                                                            kingdom.id,
                                                            unitType,
                                                        )}
                                                        onChange={(e) =>
                                                            this.handleUnitAmountChange(
                                                                kingdom.id,
                                                                unitType,
                                                                e.target.value,
                                                            )
                                                        }
                                                        placeholder="Amount"
                                                        className="w-2/3 px-4 py-2 border rounded text-gray-900 dark:text-white bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                    />
                                                </div>
                                            ),
                                        )}
                                    </div>
                                )}
                            </div>
                        ),
                    )}
                </div>
            </div>
        );
    }
}
