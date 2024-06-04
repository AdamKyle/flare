var __extends =
    (this && this.__extends) ||
    (function () {
        var extendStatics = function (d, b) {
            extendStatics =
                Object.setPrototypeOf ||
                ({ __proto__: [] } instanceof Array &&
                    function (d, b) {
                        d.__proto__ = b;
                    }) ||
                function (d, b) {
                    for (var p in b)
                        if (Object.prototype.hasOwnProperty.call(b, p))
                            d[p] = b[p];
                };
            return extendStatics(d, b);
        };
        return function (d, b) {
            if (typeof b !== "function" && b !== null)
                throw new TypeError(
                    "Class extends value " +
                        String(b) +
                        " is not a constructor or null",
                );
            extendStatics(d, b);
            function __() {
                this.constructor = d;
            }
            d.prototype =
                b === null
                    ? Object.create(b)
                    : ((__.prototype = b.prototype), new __());
        };
    })();
import React, { Fragment } from "react";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";
import Table from "../../../components/ui/data-tables/table";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import Ajax from "../../../lib/ajax/ajax";
import { BuildUnitsColumns } from "../table-columns/build-units-columns";
var UnitsTable = (function (_super) {
    __extends(UnitsTable, _super);
    function UnitsTable(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            error_message: null,
            success_message: null,
            loading: false,
        };
        return _this;
    }
    UnitsTable.prototype.viewUnit = function (unit) {
        this.props.view_unit(unit);
    };
    UnitsTable.prototype.createConditionalRowStyles = function () {
        var _this = this;
        return [
            {
                when: function (row) {
                    return _this.cannotBeRecruited(row);
                },
                style: {
                    backgroundColor: "#f4a0a0",
                    color: "white",
                },
            },
        ];
    };
    UnitsTable.prototype.cannotBeRecruited = function (unit) {
        var building = this.props.buildings.filter(function (building) {
            return (
                building.game_building_id ===
                unit.recruited_from.game_building_id
            );
        });
        if (building.length === 0) {
            return false;
        }
        var foundBuilding = building[0];
        return (
            foundBuilding.level < unit.required_building_level ||
            foundBuilding.is_locked
        );
    };
    UnitsTable.prototype.getOrderedUnits = function (units) {
        var reOrderedUnits = [];
        for (var i = 0; i < units.length; i++) {
            var unit = units[i];
            if (this.cannotBeRecruited(unit)) {
                reOrderedUnits.push(unit);
            } else {
                reOrderedUnits.unshift(unit);
            }
        }
        return reOrderedUnits;
    };
    UnitsTable.prototype.cancelUnitRecruitment = function (queueId) {
        var _this = this;
        if (queueId === null) {
            return;
        }
        this.setState(
            {
                success_message: null,
                error_message: null,
                loading: true,
            },
            function () {
                new Ajax()
                    .setRoute("kingdoms/recruit-units/cancel")
                    .setParameters({
                        queue_id: queueId,
                    })
                    .doAjaxCall(
                        "post",
                        function (response) {
                            _this.setState({
                                loading: false,
                                success_message: response.data.message,
                            });
                        },
                        function (error) {
                            if (typeof error.response !== "undefined") {
                                var response = error.response;
                                _this.setState({
                                    loading: false,
                                    error_message: response.data.message,
                                });
                            }
                        },
                    );
            },
        );
    };
    UnitsTable.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            this.state.error_message !== null
                ? React.createElement(
                      "div",
                      { className: "mt-4 mb-4" },
                      React.createElement(
                          DangerAlert,
                          null,
                          this.state.error_message,
                      ),
                  )
                : null,
            this.state.success_message !== null
                ? React.createElement(
                      "div",
                      { className: "mt-4 mb-4" },
                      React.createElement(
                          SuccessAlert,
                          null,
                          this.state.success_message,
                      ),
                  )
                : null,
            this.state.loading
                ? React.createElement(
                      "div",
                      { className: "mt-4 mb-4" },
                      React.createElement(LoadingProgressBar, null),
                  )
                : null,
            React.createElement(
                "div",
                { className: "max-w-[390px] md:max-w-full overflow-x-hidden" },
                React.createElement(Table, {
                    data: this.getOrderedUnits(this.props.units),
                    conditional_row_styles: this.createConditionalRowStyles(),
                    columns: BuildUnitsColumns(
                        this.viewUnit.bind(this),
                        this.cancelUnitRecruitment.bind(this),
                        this.props.units_in_queue,
                        this.props.current_units,
                        this.props.buildings,
                    ),
                    dark_table: this.props.dark_tables,
                }),
            ),
        );
    };
    return UnitsTable;
})(React.Component);
export default UnitsTable;
//# sourceMappingURL=units-table.js.map
