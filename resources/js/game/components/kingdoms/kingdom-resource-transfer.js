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
var __assign = (this && this.__assign) || function () {
    __assign = Object.assign || function(t) {
        for (var s, i = 1, n = arguments.length; i < n; i++) {
            s = arguments[i];
            for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p))
                t[p] = s[p];
        }
        return t;
    };
    return __assign.apply(this, arguments);
};
import React from "react";
import LoadingProgressBar from "../ui/progress-bars/loading-progress-bar";
import { serviceContainer } from "../../lib/containers/core-container";
import KingdomResourceTransferAjax from "./ajax/kingdom-resource-transfer-ajax";
import { formatNumber } from "../../lib/game/format-number";
import PrimaryButton from "../ui/buttons/primary-button";
import DropDown from "../ui/drop-down/drop-down";
import DangerButton from "../ui/buttons/danger-button";
import { startCase } from "lodash";
import SuccessButton from "../ui/buttons/success-button";
import Select from "react-select";
import DangerAlert from "../ui/alerts/simple-alerts/danger-alert";
import SuccessAlert from "../ui/alerts/simple-alerts/success-alert";
var KingdomResourceTransfer = (function (_super) {
    __extends(KingdomResourceTransfer, _super);
    function KingdomResourceTransfer(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            error_message: null,
            success_message: null,
            loading: true,
            requesting: false,
            kingdoms: [],
            index_to_view: 0,
            can_go_back: false,
            can_go_forward: true,
            amount_of_resources: "",
            type: null,
            use_air_ship: false,
        };
        _this.kingdomResourceTransferRequestAjax = serviceContainer().fetch(KingdomResourceTransferAjax);
        return _this;
    }
    KingdomResourceTransfer.prototype.componentDidMount = function () {
        this.kingdomResourceTransferRequestAjax.fetchKingdomsToTransferFrom(this, this.props.character_id, this.props.kingdom_id);
    };
    KingdomResourceTransfer.prototype.setAmountToRequest = function (e) {
        var _this = this;
        var value = parseInt(e.target.value) || 0;
        if (value > 5000 && !this.state.use_air_ship) {
            value = 5000;
        }
        if (value > 10000 && this.state.use_air_ship) {
            value = 10000;
        }
        this.setState({
            amount_of_resources: value > 0 ? value : "",
        }, function () {
            if (value > 0) {
                _this.setState({
                    can_go_back: false,
                    can_go_forward: false,
                });
            }
            else {
                _this.setState({
                    can_go_back: false,
                    can_go_forward: true,
                });
            }
        });
    };
    KingdomResourceTransfer.prototype.useAirShip = function (e) {
        var _this = this;
        var value = e.target.checked;
        this.setState({
            use_air_ship: value,
        }, function () {
            if (value) {
                _this.setState({
                    can_go_back: false,
                    can_go_forward: false,
                });
            }
            else if (_this.state.amount_of_resources === "") {
                _this.setState({
                    can_go_back: false,
                    can_go_forward: true,
                });
            }
        });
    };
    KingdomResourceTransfer.prototype.setTypeOfResourceToRequest = function (type) {
        this.setState({
            type: type,
        });
    };
    KingdomResourceTransfer.prototype.clearEntry = function () {
        this.setState({
            type: null,
            amount_of_resources: "",
            use_air_ship: false,
            can_go_back: false,
            can_go_forward: true,
        });
    };
    KingdomResourceTransfer.prototype.goForward = function () {
        var newIndex = this.state.index_to_view + 1;
        if (typeof this.state.kingdoms[newIndex] !== "undefined") {
            this.setState({
                index_to_view: newIndex,
                can_go_back: this.canGoBackward(newIndex),
                can_go_forward: this.canGoForward(newIndex),
            });
        }
    };
    KingdomResourceTransfer.prototype.goBack = function () {
        var newIndex = this.state.index_to_view - 1;
        if (typeof this.state.kingdoms[newIndex] !== "undefined") {
            this.setState({
                index_to_view: newIndex,
                can_go_back: this.canGoBackward(newIndex),
                can_go_forward: this.canGoForward(newIndex),
            });
        }
    };
    KingdomResourceTransfer.prototype.canGoForward = function (index) {
        return typeof this.state.kingdoms[index + 1] !== "undefined";
    };
    KingdomResourceTransfer.prototype.canGoBackward = function (index) {
        return typeof this.state.kingdoms[index - 1] !== "undefined";
    };
    KingdomResourceTransfer.prototype.createKingdomNameOptions = function () {
        return this.state.kingdoms.map(function (kingdom, index) {
            return {
                label: kingdom.kingdom_name,
                value: index,
            };
        });
    };
    KingdomResourceTransfer.prototype.setKingdomToView = function (data) {
        var index = parseInt(data.value) || 0;
        if (index > 0) {
            if (typeof this.state.kingdoms[index] !== "undefined") {
                this.setState({
                    index_to_view: index,
                    can_go_back: this.canGoBackward(index),
                    can_go_forward: this.canGoForward(index),
                });
            }
        }
    };
    KingdomResourceTransfer.prototype.defaultKingdomSelection = function () {
        var kingdom = this.state.kingdoms[this.state.index_to_view];
        return {
            label: kingdom.kingdom_name,
            value: this.state.index_to_view,
        };
    };
    KingdomResourceTransfer.prototype.renderKingdomDetailsForIndex = function (index) {
        var kingdom = this.state.kingdoms[index];
        return (React.createElement("div", { className: "grid md:grid-cols-2 gap-2" },
            React.createElement("dl", null,
                React.createElement("dt", null, "Name"),
                React.createElement("dd", null,
                    React.createElement(Select, { onChange: this.setKingdomToView.bind(this), options: this.createKingdomNameOptions(), menuPosition: "absolute", menuPlacement: "bottom", styles: {
                            menuPortal: function (base) { return (__assign(__assign({}, base), { zIndex: 9999, color: "#000000" })); },
                        }, menuPortalTarget: document.body, value: this.defaultKingdomSelection() })),
                React.createElement("dt", null, "X/Y"),
                React.createElement("dd", null,
                    kingdom.x_position,
                    "/",
                    kingdom.y_position),
                React.createElement("dt", null, "Time to travel"),
                React.createElement("dd", null,
                    kingdom.time_to_travel,
                    " Minutes")),
            React.createElement("div", { className: "border-b-2 block lg:hidden border-b-gray-300 dark:border-b-gray-600 my-3" }),
            React.createElement("dl", null,
                React.createElement("dt", null, "Current Wood"),
                React.createElement("dd", null, formatNumber(kingdom.current_wood)),
                React.createElement("dt", null, "Current Clay"),
                React.createElement("dd", null, formatNumber(kingdom.current_clay)),
                React.createElement("dt", null, "Current Stone"),
                React.createElement("dd", null, formatNumber(kingdom.current_stone)),
                React.createElement("dt", null, "Current Iron"),
                React.createElement("dd", null, formatNumber(kingdom.current_iron)),
                React.createElement("dt", null, "Current Steel"),
                React.createElement("dd", null, formatNumber(kingdom.current_steel)))));
    };
    KingdomResourceTransfer.prototype.sendOffRequest = function () {
        var _this = this;
        var kingdom = this.state.kingdoms[this.state.index_to_view];
        var params = {
            amount_of_resources: this.state.amount_of_resources,
            type_of_resource: this.state.type,
            use_air_ship: this.state.use_air_ship,
            kingdom_requesting: this.props.kingdom_id,
            kingdom_requesting_from: kingdom.kingdom_id,
        };
        this.setState({
            success_message: null,
            error_message: null,
            requesting: true,
        }, function () {
            _this.kingdomResourceTransferRequestAjax.requestResources(_this, params, _this.props.character_id);
        });
    };
    KingdomResourceTransfer.prototype.render = function () {
        var _this = this;
        var _a;
        if (this.state.loading) {
            return React.createElement(LoadingProgressBar, null);
        }
        if (this.state.kingdoms.length <= 0) {
            return (React.createElement("p", null, "You have no other kingdoms on this plane to request resources for. Or you have no other kingdoms on this plane, who have Market Places built."));
        }
        return (React.createElement("div", null,
            React.createElement("h3", null, "Kingdom Resource Request"),
            React.createElement("div", { className: "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4" }),
            React.createElement("p", null, "You can only request resources from one kingdom at a time. Below is a list of kingdoms that have resources you can request from. By default players can request a max of 5,000 resources of any or all types. If the kingdom you request from, has Airships, you can use one and increase the max to 10,000 at a time."),
            React.createElement("div", { className: "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2" }),
            this.state.error_message !== null ? (React.createElement(DangerAlert, { additional_css: "my-3" }, this.state.error_message)) : null,
            this.state.success_message !== null ? (React.createElement(SuccessAlert, { additional_css: "my-3" }, this.state.success_message)) : null,
            React.createElement("div", { className: "max-w-full md:max-w-[75%] md:mr-auto md:ml-auto" }, this.state.kingdoms.length > 0 ? (React.createElement("div", null,
                this.renderKingdomDetailsForIndex(this.state.index_to_view),
                React.createElement("div", { className: "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2" }),
                React.createElement("p", { className: "text-red-700 dark:text-red-500 italic my-2 text-center" }, "These two fields are the only required fields."),
                React.createElement("div", { className: "flex items-center mb-5" },
                    React.createElement("label", { className: "w-1/3" }, "Amount to transfer"),
                    React.createElement("div", { className: "w-1/3" },
                        React.createElement("input", { type: "number", value: this.state.amount_of_resources, onChange: this.setAmountToRequest.bind(this), className: "form-control", disabled: this.state.requesting, min: 0 })),
                    React.createElement("div", { className: "w-1/3" },
                        React.createElement("div", { className: "ml-2" },
                            React.createElement(DropDown, { menu_items: [
                                    {
                                        name: "Wood",
                                        icon_class: "fas fa-shopping-bag",
                                        on_click: function () {
                                            return _this.setTypeOfResourceToRequest("wood");
                                        },
                                    },
                                    {
                                        name: "Clay",
                                        icon_class: "ra ra-bubbling-potion",
                                        on_click: function () {
                                            return _this.setTypeOfResourceToRequest("clay");
                                        },
                                    },
                                    {
                                        name: "Stone",
                                        icon_class: "fas fa-gem",
                                        on_click: function () {
                                            return _this.setTypeOfResourceToRequest("stone");
                                        },
                                    },
                                    {
                                        name: "Iron",
                                        icon_class: "fas fa-gem",
                                        on_click: function () {
                                            return _this.setTypeOfResourceToRequest("iron");
                                        },
                                    },
                                    {
                                        name: "Steel",
                                        icon_class: "fas fa-gem",
                                        on_click: function () {
                                            return _this.setTypeOfResourceToRequest("steel");
                                        },
                                    },
                                    {
                                        name: "All",
                                        icon_class: "fas fa-gem",
                                        on_click: function () {
                                            return _this.setTypeOfResourceToRequest("all");
                                        },
                                    },
                                ], button_title: "Selected: " +
                                    ((_a = this.state.type) !== null && _a !== void 0 ? _a : "None"), selected_name: "", disabled: this.state.requesting })))),
                React.createElement("div", { className: "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2" }),
                React.createElement("div", { className: "flex items-center mb-5" },
                    React.createElement("div", { className: "w-1/3" },
                        React.createElement("label", null, "Use Air Ships?")),
                    React.createElement("div", null,
                        React.createElement("input", { type: "checkbox", onChange: this.useAirShip.bind(this), className: "form-checkbox", disabled: this.state.requesting }))),
                React.createElement("div", { className: "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2" }),
                React.createElement("h5", null, "Resources to transfer"),
                React.createElement("dl", null,
                    React.createElement("dt", null, "Amount:"),
                    React.createElement("dd", { className: "text-green-700 dark:text-green-500" },
                        this.state.amount_of_resources > 0
                            ? "+"
                            : "",
                        formatNumber(this.state.amount_of_resources > 0
                            ? this.state.amount_of_resources
                            : 0)),
                    React.createElement("dt", null, "For Resource:"),
                    React.createElement("dd", { className: "text-orange-700 dark:text-orange-500" }, this.state.type === null
                        ? "None selected"
                        : startCase(this.state.type)),
                    React.createElement("dt", null, "Use Air Ship"),
                    React.createElement("dd", null, this.state.use_air_ship ? "Yes" : "No"),
                    React.createElement("dt", null, "Population Cost"),
                    React.createElement("dd", null, "50"),
                    React.createElement("dt", null, "Spearmen Cost"),
                    React.createElement("dd", null, "75")),
                React.createElement("p", { className: "my-2" }, "When sending resources, you will also send a \"gaurd\" with the resources. They will return if the resources are delivered. They can be killed if the kingdom to be delivered to is no longer in your control."),
                this.state.requesting ? (React.createElement(LoadingProgressBar, null)) : null,
                React.createElement(DangerButton, { button_label: "Clear", on_click: this.clearEntry.bind(this), additional_css: "my-3", disabled: this.state.requesting }),
                React.createElement(SuccessButton, { button_label: "Request", on_click: this.sendOffRequest.bind(this), additional_css: "my-3 ml-2", disabled: !(this.state.amount_of_resources !== "" &&
                        this.state.type !== null) || this.state.requesting }),
                React.createElement("div", { className: "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2" }),
                React.createElement("div", { className: "my-4 flex justify-between" },
                    React.createElement(PrimaryButton, { button_label: "Previous", on_click: this.goBack.bind(this), disabled: !this.state.can_go_back ||
                            this.state.requesting }),
                    React.createElement(PrimaryButton, { button_label: "Next", on_click: this.goForward.bind(this), disabled: !this.state.can_go_forward ||
                            this.state.requesting })))) : (React.createElement("p", null, "There are no kingdoms to request resources from. Settle some more.")))));
    };
    return KingdomResourceTransfer;
}(React.Component));
export default KingdomResourceTransfer;
//# sourceMappingURL=kingdom-resource-transfer.js.map