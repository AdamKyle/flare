import React from "react";
import { serviceContainer } from "../../../../../lib/containers/core-container";
import FetchUnitQueuesAjax from "../../../ajax/fetch-unit-queues-ajax";
import SuccessAlert from "../../../../ui/alerts/simple-alerts/success-alert";
import DangerAlert from "../../../../ui/alerts/simple-alerts/danger-alert";
import LoadingProgressBar from "../../../../ui/progress-bars/loading-progress-bar";
import CapitalCityUnitQueueTableEventDefinition from "../../../event-listeners/capital-city-unit-queue-table-event-definition";
import CapitalCityUnitQueuesTableEvent from '../../../event-listeners/capital-city-unit-queues-table-event';
import debounce from "lodash/debounce";
import clsx from "clsx";
import { capitalize } from "lodash";
import TimerProgressBar from "../../../../ui/progress-bars/timer-progress-bar";

export default class UnitQueue extends React.Component<any, any> {

    private fetchUnitQueueAjax: FetchUnitQueuesAjax;
    private unitQueueListener: CapitalCityUnitQueueTableEventDefinition;

    constructor(props: any) {
        super(props);

        this.fetchUnitQueueAjax = serviceContainer().fetch(FetchUnitQueuesAjax);

        this.unitQueueListener = serviceContainer().fetch<CapitalCityUnitQueueTableEventDefinition>(
            CapitalCityUnitQueuesTableEvent
        );

        this.unitQueueListener.initialize(this, this.props.user_id);
        this.unitQueueListener.register();

        this.state = {
            loading: true,
            unit_queues: [],
            filtered_unit_queues: [],
            search_query: '',
            error_message: '',
            success_message: '',
            open_kingdom_ids: new Set<number>(),
        };
    }

    componentDidMount(): void {
        this.fetchUnitQueueAjax.fetchUnitQueueData(
            this,
            this.props.character_id,
            this.props.kingdom_id,
        );

        this.unitQueueListener.listen();
    }

    componentDidUpdate(prevProps: any, prevState: any): void {
        if (prevState.unit_queues !== this.state.unit_queues) {
            this.updateFilteredUnitData();
        }
    }

    debouncedUpdateFilteredData = debounce(() => {
        this.updateFilteredUnitData();
    }, 300);

    toggleDetails(kingdomId: number): void {
        this.setState((prevState: any) => {
            const newOpenKingdomIds = new Set(prevState.open_kingdom_ids);
            if (newOpenKingdomIds.has(kingdomId)) {
                newOpenKingdomIds.delete(kingdomId);
            } else {
                newOpenKingdomIds.add(kingdomId);
            }
            return { open_kingdom_ids: newOpenKingdomIds };
        });
    }

    updateFilteredUnitData(): void {
        const searchTerm = this.state.search_query.toLowerCase().trim();
        const openKingdomIds = new Set<number>();

        let filteredUnitData = this.state.unit_queues.filter((kingdom: any) => {
            return (
                (kingdom.kingdom_name.toLowerCase().includes(searchTerm) ||
                    kingdom.map_name.toLowerCase().includes(searchTerm)) &&
                kingdom.unit_requests.length > 0
            );
        });

        if (filteredUnitData.length <= 0 && searchTerm.length > 0) {
            filteredUnitData = this.state.unit_queues
                .map((kingdom: any) => {
                    const matchingUnits = kingdom.unit_requests.filter(
                        (unit: any) =>
                            unit.building_name.toLowerCase().includes(searchTerm)
                    );

                    if (matchingUnits.length > 0) {
                        openKingdomIds.add(kingdom.kingdom_id);
                        return {
                            ...kingdom,
                            unit_requests: matchingUnits,
                        };
                    }
                })
                .filter((kingdom: any) => kingdom !== null);
        }

        this.setState({
            filtered_unit_queues: filteredUnitData,
            open_kingdom_ids: openKingdomIds,
        });
    }

    handleSearchChange(event: React.ChangeEvent<HTMLInputElement>): void {
        const searchTerm = event.target.value;
        this.setState({ search_query: searchTerm });
        this.debouncedUpdateFilteredData();
    }

    render() {
        if (this.state.loading) {
            return <LoadingProgressBar />;
        }

        return (
            <div className="md:p-4">
                {this.state.success_message && (
                    <SuccessAlert additional_css="my-2">
                        {this.state.success_message}
                    </SuccessAlert>
                )}

                {this.state.error_message && (
                    <DangerAlert additional_css="my-2">
                        {this.state.error_message}
                    </DangerAlert>
                )}

                <input
                    type="text"
                    value={this.state.search_query}
                    onChange={(e) => this.handleSearchChange(e)}
                    placeholder="Search by kingdom or unit name"
                    className="w-full my-4 px-4 py-2 border rounded text-gray-900 dark:text-white bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    aria-label="Search by kingdom or unit name"
                />

                {this.state.filtered_unit_queues.map((kingdom: any) => (
                    <div key={kingdom.kingdom_id} className="mb-4">
                        <div
                            className={clsx(
                                "p-4 bg-gray-100 dark:bg-gray-700 shadow-md cursor-pointer",
                                {
                                    "rounded-lg": !this.state.open_kingdom_ids.has(kingdom.kingdom_id),
                                    "rounded-t-lg": this.state.open_kingdom_ids.has(kingdom.kingdom_id),
                                }
                            )}
                            onClick={() => this.toggleDetails(kingdom.kingdom_id)}
                        >
                            <div className="flex justify-between items-center">
                                <div>
                                    <h2 className="text-xl font-bold dark:text-white">
                                        {kingdom.kingdom_name}
                                    </h2>
                                    <p className="text-gray-700 dark:text-gray-300">
                                        Status: {capitalize(kingdom.status)}
                                    </p>
                                </div>
                                <i
                                    className={`fas fa-chevron-${this.state.open_kingdom_ids.has(kingdom.kingdom_id) ? 'down' : 'up'} text-gray-500 dark:text-gray-400`}
                                />
                            </div>
                            <TimerProgressBar
                                time_remaining={kingdom.total_time}
                                time_out_label={"Total Time Left"}
                            />
                        </div>

                        {this.state.open_kingdom_ids.has(kingdom.kingdom_id) && (
                            <div className="bg-gray-300 dark:bg-gray-600 p-4">
                                {kingdom.unit_requests.map((unit: any) => (
                                    <div
                                        key={unit.queue_id}
                                        className="mb-4 p-4 bg-white dark:bg-gray-800 shadow-sm rounded-lg"
                                    >
                                        <h3 className="text-lg font-semibold dark:text-white">
                                            {unit.building_name}
                                        </h3>
                                        <p className="text-gray-700 dark:text-gray-300">
                                            Amount to Recruit: {unit.amount_to_recruit}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                ))}

                {this.state.filtered_unit_queues.length <= 0 && (
                    <p>There are no units in queue</p>
                )}
            </div>
        );
    }
}
