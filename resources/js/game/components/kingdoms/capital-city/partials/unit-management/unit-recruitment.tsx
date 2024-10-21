import React from "react";
import FetchKingdomsForSelectionAjax from "../../../ajax/fetch-kingdoms-for-selection-ajax";
import { serviceContainer } from "../../../../../lib/containers/core-container";
import LoadingProgressBar from "../../../../ui/progress-bars/loading-progress-bar";
import debounce from "lodash/debounce";
import { UnitTypes } from "../../../deffinitions/unit-types";
import ProcessUnitRequestAjax from "../../../ajax/process-unit-request-ajax";
import SuccessAlert from "../../../../ui/alerts/simple-alerts/success-alert";
import DangerAlert from "../../../../ui/alerts/simple-alerts/danger-alert";
import UnitTopLevelActions from "./unit-top-level-actions";
import KingdomCard from "./kingdom-card";
import Pagination from "../../components/pagination";
import CapitalCityUnitRecruitmentEvent from "../../../event-listeners/capital-city-unit-recruitment-event ";
import CapitalCityUnitRecruitmentEventDefinition from "../../../event-listeners/capital-city-unit-recruitment-event-definition";

const MAX_ITEMS_PER_PAGE = 10;

export default class UnitRecruitment extends React.Component<any, any> {
    private fetchKingdomsForSelectionAjax: FetchKingdomsForSelectionAjax;

    private processUnitRequest: ProcessUnitRequestAjax;

    private unitRecruitmentEvent: CapitalCityUnitRecruitmentEventDefinition;

    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            processing_request: false,
            success_message: null,
            error_message: null,
            unit_recruitment_data: [],
            filtered_unit_recruitment_data: [],
            open_kingdom_ids: new Set(),
            search_term: "",
            global_bulk_value: "",
            unit_queue: [],
            bulk_input_values: {},
            items_per_page: MAX_ITEMS_PER_PAGE,
            current_page: 1,
        };

        this.fetchKingdomsForSelectionAjax = serviceContainer().fetch(
            FetchKingdomsForSelectionAjax,
        );

        this.processUnitRequest = serviceContainer().fetch(
            ProcessUnitRequestAjax,
        );

        this.unitRecruitmentEvent =
            serviceContainer().fetch<CapitalCityUnitRecruitmentEventDefinition>(
                CapitalCityUnitRecruitmentEvent,
            );

        this.unitRecruitmentEvent.initialize(this, this.props.user_id);
        this.unitRecruitmentEvent.register();
    }

    componentDidMount() {
        this.fetchKingdomsForSelectionAjax.fetchDetails(
            this,
            this.props.kingdom.character_id,
            this.props.kingdom.id,
        );

        this.unitRecruitmentEvent.listen();
    }

    componentDidUpdate(prevProps: any, prevState: any) {
        if (
            prevState.unit_recruitment_data !== this.state.unit_recruitment_data
        ) {
            this.updateFilteredUnitData();
        }

        if (prevState.filtered_unit_recruitment_data.length != this.state.filtered_unit_recruitment_data.length) {
            this.setState({ current_page: 1 });
        }
    }

    updateFilteredUnitData() {
        const searchTerm = this.state.search_term.toLowerCase();

        const openKingdomIds = new Set<number>();

        let filteredData = this.state.unit_recruitment_data.filter(
            (kingdom: any) => {
                return (
                    kingdom.name.toLowerCase().includes(searchTerm) ||
                    kingdom.game_map_name.toLowerCase().includes(searchTerm)
                );
            },
        );

        const unitTypes = Object.values(UnitTypes).map((v: string) =>
            v.toLowerCase(),
        );

        if (unitTypes.includes(searchTerm) && filteredData.length === 0) {
            filteredData = this.state.unit_recruitment_data;

            this.state.unit_recruitment_data.forEach((kingdom: any) => {
                openKingdomIds.add(kingdom.id);
            });
        }

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

    reset() {
        this.setState(
            {
                search_term: "",
                unit_queue: [],
                bulk_input_values: {},
                global_bulk_value: "",
            },
            () => {
                this.updateFilteredUnitData();
            },
        );
    }

    sendOrders = () => {
        this.setState(
            {
                processing_request: true,
                success_message: null,
                error_message: null,
            },
            () => {
                this.processUnitRequest.processRequest(
                    this,
                    this.props.kingdom.character_id,
                    this.props.kingdom.id,
                    this.state.unit_queue,
                );
            },
        );
    };

    handleUnitAmountChange(
        kingdomId: number,
        unitName: string,
        amount: number | string,
        returnArray: boolean,
    ) {
        let updatedQueue = [...this.state.unit_queue];

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

        if (amount === 0 || amount === "") {
            if (unitRequest) {
                kingdomQueue.unit_requests = kingdomQueue.unit_requests.filter(
                    (request: any) => request.unit_name !== unitName,
                );
            }

            if (kingdomQueue.unit_requests.length === 0) {
                updatedQueue = updatedQueue.filter(
                    (item) => item.kingdom_id !== kingdomId,
                );
            }
        } else {
            if (unitRequest) {
                unitRequest.unit_amount = amount;
            } else {
                kingdomQueue.unit_requests.push({
                    unit_name: unitName,
                    unit_amount: amount,
                });
            }
        }

        if (returnArray) {
            return updatedQueue;
        }

        this.setState({ unit_queue: updatedQueue });
    }

    getKingdomQueueSummary(kingdomId: number): string | null {
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

    getUnitAmount(kingdomId: number, unitName: string): number | string {
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

    isBulkQueueDisabled() {
        return Object.values(UnitTypes).includes(this.state.search_term);
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
        const kingdom = this.state.filtered_unit_recruitment_data.find(
            (kingdom: any) => kingdom.id === kingdomId,
        );

        if (kingdom) {
            Object.values(UnitTypes).forEach((unitType: string) => {
                this.handleUnitAmountChange(
                    kingdom.id,
                    unitType,
                    bulkAmount,
                    false,
                );
            });
        }
    }

    handleGlobalBulkAmountChang(event: React.ChangeEvent<HTMLInputElement>) {
        const bulkAmount = parseInt(event.target.value, 10) || "";
        this.setState(
            {
                global_bulk_value: bulkAmount,
            },
            () => {
                this.applyGlobalBulkAmount(bulkAmount);
            },
        );
    }

    applyGlobalBulkAmount(bulkAmount: number | string) {
        const { filtered_unit_recruitment_data } = this.state;

        let updatedQueue: any = [];

        if (bulkAmount === 0 || bulkAmount === "") {
            this.setState({
                unit_queue: updatedQueue,
            });

            return;
        }

        filtered_unit_recruitment_data.forEach((kingdom: any) => {
            const queueData: any = {
                kingdom_id: kingdom.id,
                unit_requests: [],
            };

            this.fetchUnitsToShow().forEach((unitType: string) => {
                queueData.unit_requests.push({
                    unit_name: unitType,
                    unit_amount: bulkAmount,
                });
            });

            updatedQueue.push(queueData);
        });

        this.setState({
            unit_queue: updatedQueue,
        });
    }

    manageCardState(kingdomId: number): void {
        this.setState((prevState: any) => {
            const newOpenKingdomIds = new Set(prevState.open_kingdom_ids);
            if (newOpenKingdomIds.has(kingdomId)) {
                newOpenKingdomIds.delete(kingdomId);
            } else {
                newOpenKingdomIds.add(kingdomId);
            }

            return {
                open_kingdom_ids: newOpenKingdomIds,
            };
        });
    }

    getBulkInputValue(kingdomId: number): number | string {
        return this.state.bulk_input_values[kingdomId] || "";
    }

    handlePageChange(pageNumber: number) {
        this.setState({ current_page: pageNumber });
    }

    getPaginatedData() {
        const { current_page, items_per_page, filtered_unit_recruitment_data } =
            this.state;
        const startIndex = (current_page - 1) * items_per_page;
        const endIndex = startIndex + items_per_page;

        return filtered_unit_recruitment_data.slice(startIndex, endIndex);
    }

    render() {
        if (this.state.loading) {
            return <LoadingProgressBar />;
        }

        return (
            <div className="md:p-4">
                <h3>Recruit Units to your cause</h3>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4"></div>
                {this.state.processing_request ? <LoadingProgressBar /> : null}
                {this.state.success_message !== null ? (
                    <SuccessAlert additional_css={"my-2"}>
                        {this.state.success_message}
                    </SuccessAlert>
                ) : null}
                {this.state.error_message !== null ? (
                    <DangerAlert additional_css={"my-2"}>
                        {this.state.error_message}
                    </DangerAlert>
                ) : null}
                <UnitTopLevelActions
                    search_term={this.state.search_term}
                    send_orders={this.sendOrders.bind(this)}
                    reset={this.reset.bind(this)}
                    handle_search_change={this.handleSearchChange.bind(this)}
                    actions_disabled={this.state.processing_request}
                    unit_queue={this.state.unit_queue}
                />

                <div className="my-4">
                    <label
                        htmlFor="global-bulk-recruitment"
                        className="block text-gray-700 dark:text-gray-300 font-bold"
                    >
                        Global Bulk Recruitment for All Kingdoms:
                    </label>
                    <input
                        type="number"
                        id="global-bulk-recruitment"
                        value={this.state.global_bulk_value}
                        onChange={this.handleGlobalBulkAmountChang.bind(this)}
                        className="w-full mt-2 px-4 py-2 border rounded text-gray-900 dark:text-white bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        aria-label="Global bulk recruitment for all kingdoms"
                        disabled={this.state.processing_request}
                    />
                </div>

                <div className="mb-4">
                    {this.getPaginatedData().map((kingdom: any) => (
                        <KingdomCard
                            kingdom={kingdom}
                            manage_card_state={this.manageCardState.bind(this)}
                            unit_queue={this.state.unit_queue}
                            open_kingdom_ids={this.state.open_kingdom_ids}
                            get_bulk_input_value={this.getBulkInputValue.bind(
                                this,
                            )}
                            handle_bulk_manage_card_stateamount_change={this.handleBulkAmountChange.bind(
                                this,
                            )}
                            is_bulk_queue_disabled={this.isBulkQueueDisabled.bind(
                                this,
                            )}
                            fetch_units_to_show={this.fetchUnitsToShow.bind(
                                this,
                            )}
                            get_unit_amount={this.getUnitAmount.bind(this)}
                            handle_unit_amount_change={this.handleUnitAmountChange.bind(
                                this,
                            )}
                            get_kingdom_queue_summary={this.getKingdomQueueSummary.bind(
                                this,
                            )}
                        />
                    ))}
                </div>
                <Pagination
                    on_page_change={this.handlePageChange.bind(this)}
                    current_page={this.state.currentPage}
                    items_per_page={MAX_ITEMS_PER_PAGE}
                    total_items={
                        this.state.filtered_unit_recruitment_data.length
                    }
                />
            </div>
        );
    }
}
