import { AxiosError, AxiosResponse } from "axios";
import React, { Fragment } from "react";
import DangerAlert from "../../ui/alerts/simple-alerts/danger-alert";
import SuccessAlert from "../../ui/alerts/simple-alerts/success-alert";
import Table from "../../ui/data-tables/table";
import LoadingProgressBar from "../../ui/progress-bars/loading-progress-bar";
import Ajax from "../../../../admin/lib/ajax/ajax";
import BuildingDetails from "../buildings/deffinitions/building-details";
import { BuildUnitsColumns } from "../table-columns/build-units-columns";
import UnitsTableProps from "../types/units-table-props";
import UpgradeTablesState from "../types/upgrade-tables-state";
import UnitDetails from "../deffinitions/unit-details";

export default class UnitsTable extends React.Component<
    UnitsTableProps,
    UpgradeTablesState
> {
    constructor(props: any) {
        super(props);

        this.state = {
            error_message: null,
            success_message: null,
            loading: false,
        };
    }

    viewUnit(unit: UnitDetails) {
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
        if (queueId === null) {
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
            <Fragment>
                {this.state.error_message !== null ? (
                    <div className="mt-4 mb-4">
                        <DangerAlert>{this.state.error_message}</DangerAlert>
                    </div>
                ) : null}
                {this.state.success_message !== null ? (
                    <div className="mt-4 mb-4">
                        <SuccessAlert>
                            {this.state.success_message}
                        </SuccessAlert>
                    </div>
                ) : null}
                {this.state.loading ? (
                    <div className="mt-4 mb-4">
                        <LoadingProgressBar />
                    </div>
                ) : null}
                <div
                    className={"max-w-[390px] md:max-w-full overflow-x-hidden"}
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
                        )}
                        dark_table={this.props.dark_tables}
                    />
                </div>
            </Fragment>
        );
    }
}
