import React from "react";
import FetchBuildingQueuesAjax from "../ajax/fetch-building-queues-ajax";
import { serviceContainer } from "../../../lib/containers/core-container";
import LoadingProgressBar from "../../ui/progress-bars/loading-progress-bar";
import SuccessAlert from "../../ui/alerts/simple-alerts/success-alert";
import DangerAlert from "../../ui/alerts/simple-alerts/danger-alert";
import Table from "../../ui/data-tables/table";
import { buildSmallCouncilBuildingsQueuesTableColumns } from "../table-columns/build-small-council-building-queues-table-columns";
import { viewPortWatcher } from "../../../lib/view-port-watcher";
import CapitalCityBuildingQueueTableEventDefinition from "../event-listeners/capital-city-building-queue-table-event-definition";
import CapitalCityBuildingQueuesTableEvent from "../event-listeners/capital-city-building-queues-table-event";
import { buildSmallCouncilUnitQueuesTableColumns } from "../table-columns/build-small-council-unit-queues-table-columns";
import FetchUnitQueuesAjax from "../ajax/fetch-unit-queues-ajax";
import CapitalCityUnitQueueTableEventDefinition from "../event-listeners/capital-city-unit-queue-table-event-definition";
import CapitalCityUnitQueuesTableEvent from "../event-listeners/capital-city-unit-queues-table-event";

export default class UnitQueuesTable extends React.Component<any, any> {
    private fetchUnitQueueData: FetchUnitQueuesAjax;

    private updateUnitQueueTableEvent: CapitalCityUnitQueueTableEventDefinition;

    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            view_port: 0,
            unit_queues: [],
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
                    dark_table={false}
                />
            </div>
        );
    }
}
