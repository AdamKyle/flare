import React from "react";
import { serviceContainer } from "../../../lib/containers/core-container";
import LoadingProgressBar from "../../ui/progress-bars/loading-progress-bar";
import Table from "../../ui/data-tables/table";
import { viewPortWatcher } from "../../../lib/view-port-watcher";
import { buildSmallCouncilUnitQueuesTableColumns } from "../table-columns/build-small-council-unit-queues-table-columns";
import FetchUnitQueuesAjax from "../ajax/fetch-unit-queues-ajax";
import CapitalCityUnitQueueTableEventDefinition from "../event-listeners/capital-city-unit-queue-table-event-definition";
import CapitalCityUnitQueuesTableEvent from "../event-listeners/capital-city-unit-queues-table-event";
import { watchForDarkMode } from "../../ui/helpers/watch-for-dark-mode";

export default class UnitQueuesTable extends React.Component<any, any> {
    private fetchUnitQueueData: FetchUnitQueuesAjax;

    private updateUnitQueueTableEvent: CapitalCityUnitQueueTableEventDefinition;

    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            view_port: 0,
            unit_queues: [],
            dark_tables: false,
        };

        this.fetchUnitQueueData = serviceContainer().fetch(FetchUnitQueuesAjax);

        this.updateUnitQueueTableEvent =
            serviceContainer().fetch<CapitalCityUnitQueueTableEventDefinition>(
                CapitalCityUnitQueuesTableEvent,
            );

        this.updateUnitQueueTableEvent.initialize(this, this.props.user_id);

        this.updateUnitQueueTableEvent.register();
    }

    componentDidMount() {
        viewPortWatcher(this);
        watchForDarkMode(this);

        this.fetchUnitQueueData.fetchUnitQueueData(
            this,
            this.props.character_id,
            this.props.kingdom_id,
        );

        this.updateUnitQueueTableEvent.listen();
    }

    render() {
        if (this.state.loading) {
            return <LoadingProgressBar />;
        }

        return (
            <div>
                <Table
                    columns={buildSmallCouncilUnitQueuesTableColumns(this)}
                    data={this.state.unit_queues}
                    dark_table={this.state.dark_tables}
                />
            </div>
        );
    }
}
