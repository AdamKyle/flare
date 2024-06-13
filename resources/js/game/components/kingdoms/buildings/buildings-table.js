var __extends = (this && this.__extends) || (function () {
    var extendStatics = function (d, b) {
        extendStatics = Object.setPrototypeOf ||
            ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
            function (d, b) { for (var p in b) if (Object.prototype.hasOwnProperty.call(b, p)) d[p] = b[p]; };
        return extendStatics(d, b);
    };
    return function (d, b) {
        if (typeof b !== "function" && b !== null)
            throw new TypeError("Class extends value " + String(b) + " is not a constructor or null");
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
import React, { Fragment } from "react";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";
import Table from "../../../components/ui/data-tables/table";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import Ajax from "../../../lib/ajax/ajax";
import { buildBuildingsColumns } from "../table-columns/build-buildings-columns";
var BuildingsTable = (function (_super) {
    __extends(BuildingsTable, _super);
    function BuildingsTable(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            success_message: null,
            error_message: null,
            loading: false,
        };
        return _this;
    }
    BuildingsTable.prototype.viewBuilding = function (building) {
        this.props.view_building(building);
    };
    BuildingsTable.prototype.createConditionalRowStyles = function () {
        return [
            {
                when: function (row) { return row.is_locked; },
                style: {
                    backgroundColor: "#f4a0a0",
                    color: "white",
                },
            },
        ];
    };
    BuildingsTable.prototype.cancelBuildingQueue = function (queueId) {
        var _this = this;
        if (queueId === null) {
            return;
        }
        this.setState({
            loading: true,
            success_message: null,
            error_message: null,
        }, function () {
            new Ajax()
                .setRoute("kingdoms/building-upgrade/cancel")
                .setParameters({
                queue_id: queueId,
            })
                .doAjaxCall("post", function (response) {
                _this.setState({
                    success_message: response.data.message,
                    loading: false,
                });
            }, function (error) {
                if (typeof error.response !== "undefined") {
                    var response = error.response;
                    _this.setState({
                        error_message: response.data.message,
                        loading: false,
                    });
                }
            });
        });
    };
    BuildingsTable.prototype.render = function () {
        return (React.createElement(Fragment, null,
            this.state.error_message !== null ? (React.createElement("div", { className: "mt-4 mb-4" },
                React.createElement(DangerAlert, null, this.state.error_message))) : null,
            this.state.success_message !== null ? (React.createElement("div", { className: "mt-4 mb-4" },
                React.createElement(SuccessAlert, null, this.state.success_message))) : null,
            this.state.loading ? (React.createElement("div", { className: "mt-4 mb-4" },
                React.createElement(LoadingProgressBar, null))) : null,
            React.createElement("div", { className: "max-w-[390px] md:max-w-full overflow-x-hidden" },
                React.createElement(Table, { data: this.props.buildings, columns: buildBuildingsColumns(this.viewBuilding.bind(this), this.cancelBuildingQueue.bind(this), this.props.buildings_in_queue, this.props.view_port), dark_table: this.props.dark_tables, conditional_row_styles: this.createConditionalRowStyles() }))));
    };
    return BuildingsTable;
}(React.Component));
export default BuildingsTable;
//# sourceMappingURL=buildings-table.js.map