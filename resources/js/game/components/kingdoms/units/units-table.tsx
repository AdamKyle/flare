import { AxiosError, AxiosResponse } from "axios";
import React from "react";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";
import Table from "../../../components/ui/data-tables/table";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import Ajax from "../../../lib/ajax/ajax";
import BuildingDetails from "../buildings/deffinitions/building-details";
import { BuildUnitsColumns } from "../table-columns/build-units-columns";
import UnitsTableProps from "../types/units-table-props";
import UpgradeTablesState from "../types/upgrade-tables-state";
import UnitDetails from "../deffinitions/unit-details";

export default class UnitsTable extends React.Component<
    UnitsTableProps,
    UpgradeTablesState
> {
    constructor(props: UnitsTableProps) {
        super(props);

        this.state = {
            error_message: null,
            success_message: null,
            loading: false,
        };
    }

    viewUnit(unit: UnitDetails) {
        if (this.props.is_automation_locked) {
            return;
        }

        this.props.view_unit(unit);
    }

    createConditionalRowStyles() {
        return [
            {
                when: (row: UnitDetails) => this.cannotBeRecruited(row),
                style: {
                    backgroundColor: "#f4a0a0",
                    color: "white",
                },
            },
        ];
    }

    cannotBeRecruited(unit: UnitDetails) {
        const building = this.props.buildings.filter(
            (building: BuildingDetails) => {
                return (
                    building.game_building_id ===
                    unit.recruited_from.game_building_id
                );
            },
        );

        if (building.length === 0) {
            return false;
        }

        const foundBuilding: BuildingDetails = building[0];

        return (
            foundBuilding.level < unit.required_building_level ||
            foundBuilding.is_locked
        );
    }

    getOrderedUnits(units: UnitDetails[] | []) {
        const reOrderedUnits = [];

        for (let i = 0; i < units.length; i++) {
            const unit: UnitDetails = units[i];

            if (this.cannotBeRecruited(unit)) {
                reOrderedUnits.push(unit);
            } else {
                reOrderedUnits.unshift(unit);
            }
        }

        return reOrderedUnits;
    }

    cancelUnitRecruitment(queueId: number | null) {
        if (this.props.is_automation_locked || queueId === null) {
            return;
        }

        this.setState(
            {
                success_message: null,
                error_message: null,
                loading: true,
            },
            () => {
                new Ajax()
                    .setRoute("kingdoms/recruit-units/cancel")
                    .setParameters({
                        queue_id: queueId,
                    })
                    .doAjaxCall(
                        "post",
                        (response: AxiosResponse) => {
                            this.setState({
                                loading: false,
                                success_message: response.data.message,
                            });
                        },
                        (error: AxiosError) => {
                            if (typeof error.response !== "undefined") {
                                const response: AxiosResponse = error.response;

                                this.setState({
                                    loading: false,
                                    error_message: response.data.message,
                                });
                            }
                        },
                    );
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
                    className={
                        "max-w-[390px] md:max-w-full overflow-x-auto flex-1 min-h-0"
                    }
                >
                    <Table
                        data={this.getOrderedUnits(this.props.units)}
                        conditional_row_styles={this.createConditionalRowStyles()}
                        columns={BuildUnitsColumns(
                            this.viewUnit.bind(this),
                            this.cancelUnitRecruitment.bind(this),
                            this.props.units_in_queue,
                            this.props.current_units,
                            this.props.buildings,
                            this.props.is_automation_locked,
                        )}
                        dark_table={this.props.dark_tables}
                    />
                </div>
            </div>
        );
    }
}
