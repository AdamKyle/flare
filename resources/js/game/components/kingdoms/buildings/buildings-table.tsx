import React from "react";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";
import Table from "../../../components/ui/data-tables/table";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import { serviceContainer } from "../../../lib/containers/core-container";
import CancelBuildingInQueueAjax from "../ajax/cancel-building-in-queue-ajax";
import { buildBuildingsColumns } from "../table-columns/build-buildings-columns";
import BuildingsTableProps from "../types/buildings-table-props";
import UpgradeTablesState from "../types/upgrade-tables-state";
import BuildingDetails from "./deffinitions/building-details";

export default class BuildingsTable extends React.Component<
    BuildingsTableProps,
    UpgradeTablesState
> {
    private cancelBuildingQueueAjax: CancelBuildingInQueueAjax;

    private tableContainer: React.RefObject<HTMLDivElement>;

    private resizeObserver: ResizeObserver | null = null;

    constructor(props: BuildingsTableProps) {
        super(props);

        this.state = {
            success_message: null,
            error_message: null,
            loading: false,
            rows_per_page: 5,
        };

        this.tableContainer = React.createRef();

        this.cancelBuildingQueueAjax = serviceContainer().fetch(
            CancelBuildingInQueueAjax,
        );
    }

    componentDidMount() {
        const updateRowsPerPage = () => {
            const height = this.tableContainer.current?.clientHeight ?? 0;
            const rowsPerPage = Math.max(5, Math.floor((height - 120) / 48));

            if (rowsPerPage !== this.state.rows_per_page) {
                this.setState({
                    rows_per_page: rowsPerPage,
                });
            }
        };

        updateRowsPerPage();

        if (this.tableContainer.current !== null) {
            this.resizeObserver = new ResizeObserver(updateRowsPerPage);
            this.resizeObserver.observe(this.tableContainer.current);
        }
    }

    componentWillUnmount() {
        this.resizeObserver?.disconnect();
    }

    viewBuilding(building: BuildingDetails) {
        this.props.view_building(building);
    }

    createConditionalRowStyles() {
        return [
            {
                when: (row: BuildingDetails) => row.is_locked,
                style: {
                    backgroundColor: "#f4a0a0",
                    color: "white",
                },
            },
        ];
    }

    cancelBuildingQueue(queueId: number | null) {
        if (this.props.is_automation_locked || queueId === null) {
            return;
        }

        this.setState(
            {
                loading: true,
                success_message: null,
                error_message: null,
            },
            () => {
                this.cancelBuildingQueueAjax.cancelQueue(this, queueId);
            },
        );
    }

    render() {
        return (
            <div className="h-full min-h-0 flex flex-col">
                {this.state.error_message !== null ? (
                    <div className="mt-4 mb-4 shrink-0">
                        <DangerAlert>{this.state.error_message}</DangerAlert>
                    </div>
                ) : null}
                {this.state.success_message !== null ? (
                    <div className="mt-4 mb-4 shrink-0">
                        <SuccessAlert>
                            {this.state.success_message}
                        </SuccessAlert>
                    </div>
                ) : null}
                {this.state.loading ? (
                    <div className="mt-4 mb-4 shrink-0">
                        <LoadingProgressBar />
                    </div>
                ) : null}
                <div
                    ref={this.tableContainer}
                    className={
                        "max-w-[390px] md:max-w-full overflow-x-auto flex-1 min-h-0"
                    }
                >
                    <div className="h-auto">
                        <Table
                            key={`buildings-table-${this.state.rows_per_page}`}
                            data={this.props.buildings}
                            columns={buildBuildingsColumns(
                                this.viewBuilding.bind(this),
                                this.cancelBuildingQueue.bind(this),
                                this.props.buildings_in_queue,
                                this.props.view_port,
                                this.props.is_automation_locked,
                            )}
                            dark_table={this.props.dark_tables}
                            conditional_row_styles={this.createConditionalRowStyles()}
                            pagination_per_page={this.state.rows_per_page}
                            pagination_rows_per_page_options={[
                                this.state.rows_per_page,
                                this.state.rows_per_page + 5,
                                this.state.rows_per_page + 10,
                            ]}
                        />
                    </div>
                </div>
            </div>
        );
    }
}
