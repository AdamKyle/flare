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
import React from "react";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";
import DangerOutlineButton from "../../../components/ui/buttons/danger-outline-button";
import BasicCard from "../../../components/ui/cards/basic-card";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import TimerProgressBar from "../../../components/ui/progress-bars/timer-progress-bar";
import Ajax from "../../../lib/ajax/ajax";
import { serviceContainer } from "../../../lib/containers/core-container";
import CoreEventListener from "../../../lib/game/event-listeners/core-event-listener";
import { unitMovementReasonIcon } from "../helpers/unit-movement-reason-icon";
import CancellationAjax from "./ajax/cancellation-ajax";
import { CancellationType } from "./enums/cancellation-type";
import { QueueTypes } from "./enums/queue-types";
var KingdomQueues = (function (_super) {
    __extends(KingdomQueues, _super);
    function KingdomQueues(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            loading: true,
            error_message: null,
            success_message: null,
            queues: null,
        };
        _this.gameEventListener = serviceContainer().fetch(CoreEventListener);
        _this.cancellationAjax = serviceContainer().fetch(CancellationAjax);
        _this.gameEventListener.initialize();
        _this.updateKingdomQueues = _this.gameEventListener
            .getEcho()
            .private("refresh-kingdom-queues-" + _this.props.user_id);
        return _this;
    }
    KingdomQueues.prototype.componentDidMount = function () {
        var _this = this;
        new Ajax()
            .setRoute("kingdom/queues/" +
            this.props.kingdom_id +
            "/" +
            this.props.character_id)
            .doAjaxCall("get", function (result) {
            _this.setState({
                loading: false,
                queues: result.data.queues,
            });
        }, function (error) {
            _this.setState({
                loading: false,
            });
            if (typeof error.response !== "undefined") {
                var result = error.response;
                _this.setState({
                    error_message: result.data.message,
                });
            }
        });
        this.updateKingdomQueues.listen("Game.Kingdoms.Events.UpdateKingdomQueues", function (event) {
            if (_this.props.kingdom_id !== event.kingdomId) {
                return;
            }
            _this.setState({
                queues: event.kingdomQueues,
            });
        });
    };
    KingdomQueues.prototype.cancelQueue = function (cancellationType, queueIndex, queueKey) {
        if (this.state.queues === null) {
            return;
        }
        this.cancellationAjax.doAjaxCall(this, cancellationType, this.state.queues[queueKey][queueIndex], this.props.character_id);
    };
    KingdomQueues.prototype.renderBuildingQueues = function () {
        var _this = this;
        if (this.state.queues === null) {
            return null;
        }
        var buildingQueues = this.state.queues.building_queues
            .map(function (buildingQueue, index) {
            if (buildingQueue.type === "upgrading") {
                return (React.createElement(BasicCard, { additionalClasses: "my-2" },
                    React.createElement("div", { className: "bold my-4 text-gray-800 dark:text-gray-300" },
                        "Upgrading ",
                        buildingQueue.name),
                    React.createElement(TimerProgressBar, { time_out_label: "From Level: " +
                            buildingQueue.from_level +
                            " To Level: " +
                            buildingQueue.to_level, time_remaining: buildingQueue.time_remaining }),
                    React.createElement(DangerOutlineButton, { button_label: "Cancel", on_click: function () {
                            _this.cancelQueue(CancellationType.BUILDING_IN_QUEUE, index, QueueTypes.BUILDING_QUEUES);
                        }, additional_css: "my-2" })));
            }
            if (buildingQueue.type === "repairing") {
                return (React.createElement(BasicCard, { additionalClasses: "my-2" },
                    React.createElement("div", { className: "bold my-2" },
                        "Repairing ",
                        buildingQueue.name),
                    React.createElement(TimerProgressBar, { time_out_label: "Repairing", time_remaining: buildingQueue.time_remaining }),
                    React.createElement(DangerOutlineButton, { button_label: "Cancel", on_click: function () {
                            _this.cancelQueue(CancellationType.BUILDING_IN_QUEUE, index, QueueTypes.BUILDING_QUEUES);
                        }, additional_css: "my-2" })));
            }
        })
            .filter(function (buildingQueueData) {
            return typeof buildingQueueData !== "undefined";
        });
        return buildingQueues;
    };
    KingdomQueues.prototype.renderUnitRecruitmentQueues = function () {
        var _this = this;
        if (this.state.queues === null) {
            return null;
        }
        return this.state.queues.unit_recruitment_queues.map(function (unitRecruitmentQueue, index) {
            return (React.createElement(BasicCard, { additionalClasses: "my-2" },
                React.createElement("div", { className: "bold my-2" },
                    "Recruiting ",
                    unitRecruitmentQueue.name),
                React.createElement(TimerProgressBar, { time_out_label: "Rectuiting: " +
                        unitRecruitmentQueue.recruit_amount, time_remaining: unitRecruitmentQueue.time_remaining }),
                React.createElement(DangerOutlineButton, { button_label: "Cancel", on_click: function () {
                        _this.cancelQueue(CancellationType.UNIT_RECRUITMENT, index, QueueTypes.UNIT_RECRUITMENT_QUEUES);
                    }, additional_css: "my-2" })));
        });
    };
    KingdomQueues.prototype.renderUnitMovementQueues = function () {
        var _this = this;
        if (this.state.queues === null) {
            return null;
        }
        return this.state.queues.unit_movement_queues.map(function (unitMovementQueue, index) {
            var canCancelAttack = true;
            if (unitMovementQueue.reason === "Currently attacking") {
                canCancelAttack =
                    _this.props.kingdoms.filter(function (kingdom) {
                        return (kingdom.name ===
                            unitMovementQueue.from_kingdom_name);
                    }).length > 0;
            }
            var canCancelRecall = true;
            return (React.createElement(BasicCard, { additionalClasses: "my-2" },
                React.createElement("div", { className: "bold my-2" }, "Units Are on the move!"),
                React.createElement("dl", { className: "my-4" },
                    React.createElement("dt", null, "Why"),
                    React.createElement("dd", null,
                        unitMovementReasonIcon(unitMovementQueue),
                        " ",
                        unitMovementQueue.reason),
                    React.createElement("dt", null, "From:"),
                    React.createElement("dd", null,
                        unitMovementQueue.from_kingdom_name,
                        " (X/Y:",
                        " ",
                        unitMovementQueue.from_x,
                        "/",
                        unitMovementQueue.from_y,
                        ")"),
                    React.createElement("dt", null, "To:"),
                    React.createElement("dd", null,
                        unitMovementQueue.to_kingdom_name,
                        " (X/Y:",
                        " ",
                        unitMovementQueue.moving_to_y,
                        "/",
                        unitMovementQueue.moving_to_y,
                        ")")),
                React.createElement(TimerProgressBar, { time_out_label: "Units are in movement", time_remaining: unitMovementQueue.time_left }),
                React.createElement(DangerOutlineButton, { button_label: "Cancel", on_click: function () {
                        _this.cancelQueue(CancellationType.UNIT_MOVEMENT, index, QueueTypes.UNIT_MOVEMENT_QUEUES);
                    }, additional_css: "my-2", disabled: ((!canCancelAttack || !canCancelRecall) &&
                        unitMovementQueue.reason ===
                            "Returning from attack") ||
                        unitMovementQueue.reason === "Recalled units" })));
        });
    };
    KingdomQueues.prototype.renderBuildingExpansionQueues = function () {
        var _this = this;
        if (this.state.queues === null) {
            return null;
        }
        return this.state.queues.building_expansion_queues.map(function (buildingExpansionQueue, index) {
            return (React.createElement(BasicCard, { additionalClasses: "my-2" },
                React.createElement("div", { className: "bold my-2" },
                    buildingExpansionQueue.name,
                    " Is expanding production"),
                React.createElement(TimerProgressBar, { time_out_label: "From slot: " +
                        buildingExpansionQueue.from_slot +
                        " to slot: " +
                        buildingExpansionQueue.to_slot, time_remaining: buildingExpansionQueue.time_remaining }),
                React.createElement(DangerOutlineButton, { button_label: "Cancel", on_click: function () {
                        _this.cancelQueue(CancellationType.BUILDING_EXPANSION, index, QueueTypes.BUILDING_EXPANSION_QUEUES);
                    }, additional_css: "my-2" })));
        });
    };
    KingdomQueues.prototype.render = function () {
        return (React.createElement("div", null,
            React.createElement("p", { className: "my-2" }, "Below you will find the various queues. This could be building expansions, repairs, upgrades, unit recruitment and movement."),
            React.createElement("div", { className: "border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3" }),
            this.state.loading ? React.createElement(LoadingProgressBar, null) : null,
            this.state.error_message !== null ? (React.createElement(DangerAlert, null, this.state.error_message)) : null,
            this.state.success_message !== null ? (React.createElement(SuccessAlert, null, this.state.success_message)) : null,
            React.createElement("div", { className: "w-[90%] mr-auto ml-auto max-h-[600px] overflow-y-auto" },
                this.renderBuildingQueues(),
                this.renderBuildingExpansionQueues(),
                this.renderUnitRecruitmentQueues(),
                this.renderUnitMovementQueues())));
    };
    return KingdomQueues;
}(React.Component));
export default KingdomQueues;
//# sourceMappingURL=kingdom-queues.js.map