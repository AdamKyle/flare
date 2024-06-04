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
import Table from "../../../components/ui/data-tables/table";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import Ajax from "../../../lib/ajax/ajax";
import { BuildUnitsInMovementColumns } from "../table-columns/build-units-in-movement-columns";
var UnitsMovementTable = (function (_super) {
    __extends(UnitsMovementTable, _super);
    function UnitsMovementTable(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            loading: false,
            error_message: "",
        };
        return _this;
    }
    UnitsMovementTable.prototype.cancelUnitRecruitment = function (queueId) {
        var _this = this;
        this.setState(
            {
                error_message: "",
                loading: true,
            },
            function () {
                new Ajax()
                    .setRoute(
                        "recall-units/" +
                            queueId +
                            "/" +
                            _this.props.character_id,
                    )
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState({
                                loading: false,
                            });
                        },
                        function (error) {
                            _this.setState({ loading: false });
                            if (typeof error.response !== "undefined") {
                                var response = error.response;
                                _this.setState({
                                    error_message: response.data.message,
                                });
                            }
                        },
                    );
            },
        );
    };
    UnitsMovementTable.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            this.state.error_message !== ""
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
            this.state.loading
                ? React.createElement(
                      "div",
                      { className: "mt-4 mb-4" },
                      React.createElement(LoadingProgressBar, null),
                  )
                : null,
            React.createElement(Table, {
                data: this.props.units_in_movement,
                columns: BuildUnitsInMovementColumns(
                    this.cancelUnitRecruitment.bind(this),
                    this.props.units_in_movement,
                ),
                dark_table: this.props.dark_tables,
            }),
        );
    };
    return UnitsMovementTable;
})(React.Component);
export default UnitsMovementTable;
//# sourceMappingURL=units-movement-table.js.map
