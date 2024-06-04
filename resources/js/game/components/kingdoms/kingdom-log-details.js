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
import clsx from "clsx";
import React, { Fragment } from "react";
import InfoAlert from "../../components/ui/alerts/simple-alerts/info-alert";
import BasicCard from "../../components/ui/cards/basic-card";
import { formatNumber } from "../../lib/game/format-number";
import { startCase } from "lodash";
var KingdomLogDetails = (function (_super) {
    __extends(KingdomLogDetails, _super);
    function KingdomLogDetails(props) {
        return _super.call(this, props) || this;
    }
    KingdomLogDetails.prototype.renderBuildingChanges = function () {
        var _this = this;
        var changes = [];
        this.props.log.old_buildings.forEach(function (oldBuilding) {
            var foundNewBuilding = _this.props.log.new_buildings.filter(function (newBuilding) {
                return newBuilding.name === oldBuilding.name;
            });
            if (foundNewBuilding.length > 0) {
                var newBuilding = foundNewBuilding[0];
                if (newBuilding.durability === oldBuilding.durability) {
                    changes.push(React.createElement(Fragment, null,
                        React.createElement("dt", null, oldBuilding.name),
                        React.createElement("dd", null,
                            "0% Lost",
                            _this.props.log.is_mine
                                ? ", New Durability: " +
                                    formatNumber(newBuilding.durability)
                                : null)));
                }
                else if (newBuilding.durability === 0) {
                    changes.push(React.createElement(Fragment, null,
                        React.createElement("dt", null, oldBuilding.name),
                        React.createElement("dd", { className: "text-red-600 dark:text-red-400" },
                            "100% Lost",
                            _this.props.log.is_mine
                                ? ", New Durability: " +
                                    formatNumber(newBuilding.durability)
                                : null)));
                }
                else {
                    changes.push(React.createElement(Fragment, null,
                        React.createElement("dt", null, oldBuilding.name),
                        React.createElement("dd", { className: "text-red-600 dark:text-red-400" },
                            (((oldBuilding.durability -
                                newBuilding.durability) /
                                oldBuilding.durability) *
                                100).toFixed(0),
                            "% Lost",
                            _this.props.log.is_mine
                                ? ", New Durability: " +
                                    formatNumber(newBuilding.durability)
                                : null)));
                }
            }
        });
        return changes;
    };
    KingdomLogDetails.prototype.renderUnitChanges = function () {
        var _this = this;
        var changes = [];
        this.props.log.old_units.forEach(function (oldUnit) {
            var foundNewUnit = _this.props.log.new_units.filter(function (newUnit) {
                return newUnit.name === oldUnit.name;
            });
            if (foundNewUnit.length > 0) {
                var newUnit = foundNewUnit[0];
                if (newUnit.amount === oldUnit.amount) {
                    changes.push(React.createElement(Fragment, null,
                        React.createElement("dt", null, oldUnit.name),
                        React.createElement("dd", null,
                            "0% Lost",
                            _this.props.log.is_mine
                                ? ", Amount Left: " +
                                    formatNumber(newUnit.amount)
                                : null)));
                }
                else if (newUnit.amount === 0) {
                    changes.push(React.createElement(Fragment, null,
                        React.createElement("dt", null, oldUnit.name),
                        React.createElement("dd", { className: "text-red-600 dark:text-red-400" },
                            "100% Lost",
                            _this.props.log.is_mine
                                ? ", Amount Left: " +
                                    formatNumber(newUnit.amount)
                                : null)));
                }
                else {
                    changes.push(React.createElement(Fragment, null,
                        React.createElement("dt", null, oldUnit.name),
                        React.createElement("dd", { className: "text-red-600 dark:text-red-400" },
                            (((oldUnit.amount - newUnit.amount) /
                                oldUnit.amount) *
                                100).toFixed(2),
                            "% Lost",
                            _this.props.log.is_mine
                                ? ", Amount Left: " +
                                    formatNumber(newUnit.amount)
                                : null)));
                }
            }
        });
        return changes;
    };
    KingdomLogDetails.prototype.renderUnitsSentChange = function () {
        var _this = this;
        var changes = [];
        this.props.log.units_sent.forEach(function (sentUnit) {
            var foundNewUnit = _this.props.log.units_survived.filter(function (newUnit) {
                return newUnit.name === sentUnit.name;
            });
            if (foundNewUnit.length > 0) {
                var newUnit = foundNewUnit[0];
                if (newUnit.amount === sentUnit.amount) {
                    changes.push(React.createElement(Fragment, null,
                        React.createElement("dt", null, sentUnit.name),
                        React.createElement("dd", null,
                            "0% Lost",
                            !_this.props.log.is_mine
                                ? ", Amount Left: " +
                                    formatNumber(newUnit.amount)
                                : null)));
                }
                else if (newUnit.amount === 0) {
                    changes.push(React.createElement(Fragment, null,
                        React.createElement("dt", null, sentUnit.name),
                        React.createElement("dd", { className: "text-red-600 dark:text-red-400" },
                            "100% Lost",
                            !_this.props.log.is_mine
                                ? ", Amount Left: " +
                                    formatNumber(newUnit.amount)
                                : null)));
                }
                else {
                    changes.push(React.createElement(Fragment, null,
                        React.createElement("dt", null, sentUnit.name),
                        React.createElement("dd", { className: "text-red-600 dark:text-red-400" },
                            (((sentUnit.amount - newUnit.amount) /
                                sentUnit.amount) *
                                100).toFixed(0),
                            "% Lost",
                            !_this.props.log.is_mine
                                ? ", Amount Left: " +
                                    formatNumber(newUnit.amount)
                                : null)));
                }
            }
        });
        return changes;
    };
    KingdomLogDetails.prototype.shouldShowUnitSentChanges = function () {
        return (this.props.log.units_sent.length > 0 &&
            this.props.log.units_survived.length > 0);
    };
    KingdomLogDetails.prototype.renderResourcesDeliveredDetails = function () {
        var _this = this;
        var resourceKeys = Object.keys(this.props.log.additional_details.resource_request_log
            .resource_details);
        return resourceKeys.map(function (resourceKey) {
            return (React.createElement(React.Fragment, null,
                React.createElement("dt", null, startCase(resourceKey)),
                React.createElement("dd", { className: "text-green-700 dark:text-green-500" },
                    "+",
                    formatNumber(_this.props.log.additional_details
                        .resource_request_log.resource_details[resourceKey]))));
        });
    };
    KingdomLogDetails.prototype.renderAdditionalResourceMovementLogDetails = function () {
        var additionalMessages = this.props.log.additional_details.resource_request_log
            .additional_messages;
        return additionalMessages.map(function (message) {
            return React.createElement("li", null, message);
        });
    };
    KingdomLogDetails.prototype.render = function () {
        if (this.props.log.status === "Kingdom requested resources") {
            return (React.createElement(BasicCard, null,
                React.createElement("div", { className: "text-right cursor-pointer text-red-500" },
                    React.createElement("button", { onClick: this.props.close_details },
                        React.createElement("i", { className: "fas fa-minus-circle" }))),
                React.createElement("p", { className: "my-4" },
                    "Kingdom: ",
                    this.props.log.to_kingdom_name,
                    " has requested resources from: ",
                    this.props.log.from_kingdom_name,
                    ". These have now been delivered."),
                React.createElement("dl", null, this.renderResourcesDeliveredDetails()),
                this.props.log.additional_details.resource_request_log
                    .additional_messages.length > 0 ? (React.createElement("div", { className: "my-4" },
                    React.createElement("p", { className: "text-yellow-700 dark:text-yellow-600 mb-4" }, "There are additional details about this trip:"),
                    React.createElement("ul", { className: "list-disc ml-4" }, this.renderAdditionalResourceMovementLogDetails()))) : null));
        }
        if (this.props.log.status === "Kingdom has not been walked") {
            return (React.createElement(BasicCard, null,
                React.createElement("div", { className: "text-right cursor-pointer text-red-500" },
                    React.createElement("button", { onClick: this.props.close_details },
                        React.createElement("i", { className: "fas fa-minus-circle" }))),
                React.createElement("div", { className: "my-4" },
                    React.createElement("h3", { className: "mb-4" }, this.props.log.status),
                    React.createElement("p", { className: "my-4 text-red-600 dark:text-red-500" }, "You have not visited your kingdom in the last 90 days. So it was handed to The Old Man and made into an NPC Kingdom."),
                    React.createElement("dl", { className: "my-4" },
                        React.createElement("dt", null, "Kingdom Name"),
                        React.createElement("dd", null, this.props.log.additional_details
                            .kingdom_data.name),
                        React.createElement("dt", null, "Kingdom Location"),
                        React.createElement("dd", null,
                            "(X/Y)",
                            " ",
                            this.props.log.additional_details
                                .kingdom_data.x,
                            " ",
                            "/",
                            " ",
                            this.props.log.additional_details
                                .kingdom_data.y),
                        React.createElement("dt", null, "On Map"),
                        React.createElement("dd", null, this.props.log.additional_details
                            .kingdom_data.game_map_name),
                        React.createElement("dt", null, "Reason"),
                        React.createElement("dd", null, this.props.log.additional_details
                            .kingdom_data.reason)),
                    React.createElement(InfoAlert, { additional_css: "my-4" },
                        React.createElement("h4", null, "Walking your kingdoms"),
                        React.createElement("p", { className: "my-4" }, "Kingdoms have to be walked at least once in a 90 day period or they get handed over to The Old Man. What it means to walk a kingdom is to physically visit the kingdom to consider it \"walked\".")))));
        }
        if (this.props.log.status === "Kingdom was overpopulated") {
            return (React.createElement(BasicCard, null,
                React.createElement("div", { className: "text-right cursor-pointer text-red-500" },
                    React.createElement("button", { onClick: this.props.close_details },
                        React.createElement("i", { className: "fas fa-minus-circle" }))),
                React.createElement("div", { className: "my-4" },
                    React.createElement("h3", { className: "mb-4" }, this.props.log.status),
                    React.createElement("p", { className: "my-4 text-red-600 dark:text-red-500" }, "You kingdom was overpopulated. The Old Man took it and demolished it."),
                    React.createElement("dl", { className: "my-4" },
                        React.createElement("dt", null, "Kingdom Name"),
                        React.createElement("dd", null, this.props.log.additional_details
                            .kingdom_data.name),
                        React.createElement("dt", null, "Kingdom Location"),
                        React.createElement("dd", null,
                            "(X/Y)",
                            " ",
                            this.props.log.additional_details
                                .kingdom_data.x,
                            " ",
                            "/",
                            " ",
                            this.props.log.additional_details
                                .kingdom_data.y),
                        React.createElement("dt", null, "On Map"),
                        React.createElement("dd", null, this.props.log.additional_details
                            .kingdom_data.game_map_name),
                        React.createElement("dt", null, "Reason"),
                        React.createElement("dd", null, this.props.log.additional_details
                            .kingdom_data.reason)),
                    React.createElement(InfoAlert, { additional_css: "my-4" },
                        React.createElement("h4", null, "Over Population"),
                        React.createElement("p", { className: "my-4" }, "Kingdoms can purchase additional population for recruiting large amount of units, but one should becarfeul because if you have more then your max at the hourly reset The Old Man will stomp around. he will attempt to:"),
                        React.createElement("ul", { className: "my-4 list-disc" },
                            React.createElement("li", { className: "ml-4" }, "Take the cost our of your gold bars"),
                            React.createElement("li", { className: "ml-4" }, "If you have none, he will take it from your treasury."),
                            React.createElement("li", { className: "ml-4" }, "If you have none, he will take it from your own gold."),
                            React.createElement("li", { className: "ml-4" }, "If you have none, he will destroy the kingdom."))))));
        }
        return (React.createElement(BasicCard, null,
            React.createElement("div", { className: "text-right cursor-pointer text-red-500" },
                React.createElement("button", { onClick: this.props.close_details },
                    React.createElement("i", { className: "fas fa-minus-circle" }))),
            React.createElement("div", { className: "my-4" },
                React.createElement("h3", { className: "mb-4" }, this.props.log.status),
                React.createElement("dl", null,
                    React.createElement("dt", null, "Kingdom Attacked (X/Y)"),
                    React.createElement("dd", { className: clsx({
                            "text-green-600 dark:text-green-400": !this.props.log.is_mine,
                            "text-red-600 dark:text-red-400": this.props.log.is_mine,
                        }) },
                        this.props.log.to_kingdom_name,
                        " ",
                        this.props.log.to_x,
                        " / ",
                        this.props.log.to_y),
                    React.createElement("dt", null, "Attacked From (X/Y)"),
                    React.createElement("dd", { className: clsx({
                            "text-green-600 dark:text-green-400": this.props.log.is_mine,
                            "text-red-600 dark:text-red-400": !this.props.log.is_mine,
                        }) }, this.props.log.from_kingdom_name !== null
                        ? this.props.log.from_kingdom_name +
                            " " +
                            this.props.log.from_x +
                            "/" +
                            this.props.log.from_y
                        : "N/A"),
                    React.createElement("dt", { className: this.props.log.took_kingdom ? "hidden" : "" }, "Kingdom Attacked Morale Loss"),
                    React.createElement("dd", { className: "text-red-600 dark:text-red-400 " +
                            this.props.log.took_kingdom
                            ? "hidden"
                            : "" },
                        (this.props.log.morale_loss * 100).toFixed(2),
                        " %")),
                React.createElement("div", { className: "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3 " +
                        (!this.props.log.took_kingdom ? "hidden" : "") }),
                React.createElement("p", { className: !this.props.log.took_kingdom ? "hidden" : "" }, "You now own this kingdom. You took it from the defender. Check your kingdoms list. Any surviving units are now held up here.")),
            React.createElement("div", { className: "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3 " +
                    this.props.log.took_kingdom
                    ? "hidden"
                    : "" }),
            React.createElement("div", { className: this.props.log.took_kingdom ? "hidden" : "" },
                React.createElement("div", { className: "grid md:grid-cols-" +
                        (this.shouldShowUnitSentChanges() ? "3" : "2") +
                        " gap-2" },
                    React.createElement("div", null,
                        React.createElement("h3", { className: "mb-4" }, "Building Changes"),
                        React.createElement("dl", null, this.renderBuildingChanges())),
                    this.props.log.old_units.length === 0 &&
                        this.props.log.new_units.length === 0 ? (React.createElement("div", null,
                        React.createElement("h3", { className: "mb-4" }, "Unit Changes"),
                        React.createElement("p", null, "There were no changes in kingdom units."))) : (React.createElement("div", null,
                        React.createElement("h3", { className: "mb-4" }, "Unit Changes"),
                        React.createElement("dl", null, this.renderUnitChanges()))),
                    this.shouldShowUnitSentChanges() ? (React.createElement("div", null,
                        React.createElement("h3", { className: "mb-4" }, "Attacking Unit Changes"),
                        React.createElement("dl", null, this.renderUnitsSentChange()))) : null))));
    };
    return KingdomLogDetails;
}(React.Component));
export default KingdomLogDetails;
//# sourceMappingURL=kingdom-log-details.js.map