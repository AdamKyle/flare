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
import InfoAlert from "../../components/ui/alerts/simple-alerts/info-alert";
import BasicCard from "../../components/ui/cards/basic-card";
import LoadingProgressBar from "../../components/ui/progress-bars/loading-progress-bar";
import Ajax from "../../lib/ajax/ajax";
import { serviceContainer } from "../../lib/containers/core-container";
import UpdateKingdomListeners from "../../lib/game/event-listeners/game/update-kingdom-listeners";
import InformationSection from "./information-section";
import KingdomDetails from "./kingdom-details";
import KingdomTabs from "./tabs/kingdom-tabs";
import KingdomResourceTransfer from "./kingdom-resource-transfer";
import SmallCouncil from "./capital-city/small-council";
var Kingdom = (function (_super) {
    __extends(Kingdom, _super);
    function Kingdom(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            loading: true,
            building_to_view: null,
            unit_to_view: null,
            error_message: null,
            kingdom: null,
            show_resource_transfer_panel: false,
            should_reset_resource_transfer: false,
            show_small_council: false,
        };
        _this.updateKingdomListener =
            serviceContainer().fetch(UpdateKingdomListeners);
        _this.updateKingdomListener.initialize(_this, _this.props.user_id);
        _this.updateKingdomListener.register();
        return _this;
    }
    Kingdom.prototype.componentDidMount = function () {
        var _this = this;
        new Ajax()
            .setRoute("player-kingdom/" +
            this.props.kingdom.character_id +
            "/" +
            this.props.kingdom.id)
            .doAjaxCall("GET", function (result) {
            _this.setState({
                loading: false,
                kingdom: result.data.kingdom,
            });
        }, function (error) {
            _this.setState({ loading: false });
            if (typeof error.response !== "undefined") {
                var response = error.response;
                _this.setState({
                    error_message: response.data.message,
                });
            }
        });
        this.updateKingdomListener.listen();
    };
    Kingdom.prototype.manageSmallCouncil = function () {
        this.setState({
            show_small_council: !this.state.show_small_council,
        });
    };
    Kingdom.prototype.manageViewBuilding = function (building) {
        this.setState({
            building_to_view: typeof building !== "undefined" ? building : null,
        });
    };
    Kingdom.prototype.manageViewUnit = function (unit) {
        this.setState({
            unit_to_view: typeof unit !== "undefined" ? unit : null,
        });
    };
    Kingdom.prototype.closeSection = function () {
        this.setState({
            building_to_view: null,
            unit_to_view: null,
        });
    };
    Kingdom.prototype.isInQueue = function () {
        var _this = this;
        if (this.state.building_to_view === null) {
            return false;
        }
        if (this.state.kingdom.building_queue.length === 0) {
            return false;
        }
        return (this.state.kingdom.building_queue.filter(function (queue) {
            return queue.building_id === _this.state.building_to_view.id;
        }).length > 0);
    };
    Kingdom.prototype.isUnitInQueue = function () {
        var _this = this;
        if (this.state.unit_to_view === null) {
            return false;
        }
        if (this.state.kingdom.unit_queue.length === 0) {
            return false;
        }
        return (this.state.kingdom.unit_queue.filter(function (queue) {
            return queue.game_unit_id === _this.state.unit_to_view.id;
        }).length > 0);
    };
    Kingdom.prototype.showResourceTransferPanel = function () {
        this.setState({
            show_resource_transfer_panel: !this.state.show_resource_transfer_panel,
            should_reset_resource_transfer: this.state.show_resource_transfer_panel,
        });
    };
    Kingdom.prototype.render = function () {
        if (this.state.loading && this.state.kingdom === null) {
            return React.createElement(LoadingProgressBar, null);
        }
        if (this.state.show_small_council) {
            return React.createElement(SmallCouncil, null);
        }
        return (React.createElement(Fragment, null,
            this.state.kingdom.is_protected ? (React.createElement(InfoAlert, { additional_css: "mt-4 mb-4" },
                "Your kingdom is under protection from attacks for the next: ",
                this.state.kingdom.protected_days_left,
                " day(s). This value does not include today.")) : null,
            React.createElement("div", { className: "grid md:grid-cols-2 gap-4" },
                this.state.show_resource_transfer_panel ? (React.createElement(BasicCard, null,
                    React.createElement("div", { className: "text-right cursor-pointer text-red-500" },
                        React.createElement("button", { onClick: this.showResourceTransferPanel.bind(this) },
                            React.createElement("i", { className: "fas fa-minus-circle" }))),
                    React.createElement(KingdomResourceTransfer, { character_id: this.props.kingdom.character_id, kingdom_id: this.props.kingdom.id }))) : (React.createElement(BasicCard, { additionalClasses: "max-h-[700px]" },
                    React.createElement("div", { className: "text-right cursor-pointer text-red-500" },
                        React.createElement("button", { onClick: this.props.close_details },
                            React.createElement("i", { className: "fas fa-minus-circle" }))),
                    React.createElement(KingdomDetails, { kingdom: this.state.kingdom, character_gold: this.props.character_gold, close_details: this.props.close_details, show_resource_transfer_card: this.showResourceTransferPanel.bind(this), show_small_council: this.manageSmallCouncil.bind(this), reset_resource_transfer: this.state.should_reset_resource_transfer }))),
                React.createElement("div", null, this.state.building_to_view !== null ||
                    this.state.unit_to_view !== null ? (React.createElement(InformationSection, { sections: {
                        unit_to_view: this.state.unit_to_view,
                        building_to_view: this.state.building_to_view,
                    }, close: this.closeSection.bind(this), cost_reduction: {
                        kingdom_building_time_reduction: this.state.kingdom
                            .building_time_reduction,
                        kingdom_building_cost_reduction: this.state.kingdom
                            .building_cost_reduction,
                        kingdom_iron_cost_reduction: this.state.kingdom.iron_cost_reduction,
                        kingdom_population_cost_reduction: this.state.kingdom
                            .population_cost_reduction,
                        kingdom_current_population: this.state.kingdom.current_population,
                        kingdom_unit_cost_reduction: this.state.kingdom.unit_cost_reduction,
                        kingdom_unit_time_reduction: this.state.kingdom.unit_time_reduction,
                    }, buildings: this.state.kingdom.buildings, queue: {
                        is_building_in_queue: this.isInQueue(),
                        is_unit_in_queue: this.isUnitInQueue(),
                    }, character_id: this.state.kingdom.character_id, kingdom_id: this.state.kingdom.id, character_gold: this.props.character_gold, user_id: this.props.user_id })) : (React.createElement(KingdomTabs, { kingdom: this.state.kingdom, kingdoms: this.props.kingdoms, dark_tables: this.props.dark_tables, manage_view_building: this.manageViewBuilding.bind(this), manage_view_unit: this.manageViewUnit.bind(this), view_port: this.props.view_port, user_id: this.props.user_id }))))));
    };
    return Kingdom;
}(React.Component));
export default Kingdom;
//# sourceMappingURL=kingdom.js.map