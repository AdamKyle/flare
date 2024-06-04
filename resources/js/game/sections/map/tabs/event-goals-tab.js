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
import React from "react";
import Ajax from "../../../lib/ajax/ajax";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import OrangeProgressBar from "../../../components/ui/progress-bars/orange-progress-bar";
import { formatNumber } from "../../../lib/game/format-number";
import { kebabCase } from "lodash";
var EventGoalsTab = (function (_super) {
    __extends(EventGoalsTab, _super);
    function EventGoalsTab(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            loading: true,
            eventGoal: null,
            player_amount: 0,
        };
        _this.eventGoalsUpdate = Echo.join("update-event-goal-progress");
        _this.playerEventGoalCurrentAmount = Echo.private(
            "player-current-event-goal-progression-" + _this.props.user_id,
        );
        return _this;
    }
    EventGoalsTab.prototype.componentDidMount = function () {
        var _this = this;
        new Ajax()
            .setRoute("global-event-goals/" + this.props.character_id)
            .doAjaxCall(
                "get",
                function (result) {
                    _this.setState(
                        {
                            loading: false,
                            eventGoal: result.data.event_goals,
                        },
                        function () {
                            _this.setCurrentAmount();
                        },
                    );
                },
                function (error) {
                    console.error(error);
                },
            );
        this.eventGoalsUpdate.listen(
            "Game.Events.Events.UpdateEventGoalProgress",
            function (event) {
                _this.setState({
                    eventGoal: event.eventGoalData.event_goals,
                });
            },
        );
        this.playerEventGoalCurrentAmount.listen(
            "Game.Events.Events.UpdateEventGoalCurrentProgressForCharacter",
            function (event) {
                _this.setState({
                    player_amount: event.amount,
                });
            },
        );
    };
    EventGoalsTab.prototype.buildProgressBars = function () {
        var progressBars = [];
        if (this.state.eventGoal === null) {
            return progressBars;
        }
        var totalDone = 0;
        var maxAmount = 0;
        var current = this.state.eventGoal.reward_every;
        var phase = 1;
        if (
            this.state.eventGoal.max_kills !== null &&
            this.state.eventGoal.total_kills !== null
        ) {
            maxAmount = this.state.eventGoal.max_kills;
            totalDone = this.state.eventGoal.total_kills;
        }
        if (
            this.state.eventGoal.max_crafts !== null &&
            this.state.eventGoal.total_crafts !== null
        ) {
            maxAmount = this.state.eventGoal.max_crafts;
            totalDone = this.state.eventGoal.total_crafts;
        }
        if (
            this.state.eventGoal.max_enchants !== null &&
            this.state.eventGoal.total_enchants !== null
        ) {
            maxAmount = this.state.eventGoal.max_enchants;
            totalDone = this.state.eventGoal.total_enchants;
        }
        while (current <= maxAmount) {
            var value = Math.min(current, totalDone);
            progressBars.push(
                React.createElement(
                    "div",
                    { className: "mb-4 relative top-[-15px]" },
                    React.createElement(OrangeProgressBar, {
                        key: current,
                        primary_label: "Phase: " + phase,
                        secondary_label:
                            formatNumber(value) + "/" + formatNumber(current),
                        percentage_filled: (value / current) * 100,
                        height_override_class: "h-2",
                        text_override_class: "text-md",
                        push_down: true,
                    }),
                ),
            );
            current += this.state.eventGoal.reward_every;
            phase++;
        }
        return progressBars;
    };
    EventGoalsTab.prototype.getEventGoalLabel = function () {
        if (this.state.eventGoal === null) {
            return "ERROR - Missing event goal.";
        }
        var label = "ERROR - undefined type of total for event goal.";
        if (
            this.state.eventGoal.total_kills !== null &&
            this.state.eventGoal.max_kills !== null
        ) {
            label =
                formatNumber(this.state.eventGoal.total_kills) +
                "/" +
                formatNumber(this.state.eventGoal.max_kills);
        }
        if (
            this.state.eventGoal.total_crafts !== null &&
            this.state.eventGoal.max_crafts !== null
        ) {
            label =
                formatNumber(this.state.eventGoal.total_crafts) +
                "/" +
                formatNumber(this.state.eventGoal.max_crafts);
        }
        if (
            this.state.eventGoal.total_enchants !== null &&
            this.state.eventGoal.max_enchants !== null
        ) {
            label =
                formatNumber(this.state.eventGoal.total_enchants) +
                "/" +
                formatNumber(this.state.eventGoal.max_enchants);
        }
        return label;
    };
    EventGoalsTab.prototype.getOverAllProgress = function () {
        if (this.state.eventGoal === null) {
            return 0;
        }
        var percentageFilled = 0;
        if (
            this.state.eventGoal.total_kills !== null &&
            this.state.eventGoal.max_kills !== null
        ) {
            percentageFilled =
                (this.state.eventGoal.total_kills /
                    this.state.eventGoal.max_kills) *
                100;
        }
        if (
            this.state.eventGoal.total_crafts !== null &&
            this.state.eventGoal.max_crafts !== null
        ) {
            percentageFilled =
                (this.state.eventGoal.total_crafts /
                    this.state.eventGoal.max_crafts) *
                100;
        }
        if (
            this.state.eventGoal.total_enchants !== null &&
            this.state.eventGoal.max_enchants !== null
        ) {
            percentageFilled =
                (this.state.eventGoal.total_enchants /
                    this.state.eventGoal.max_enchants) *
                100;
        }
        return percentageFilled > 100 ? 100 : percentageFilled;
    };
    EventGoalsTab.prototype.getTitleForProgressBar = function () {
        if (this.state.eventGoal === null) {
            return "Unknown Event Step";
        }
        if (
            this.state.eventGoal.total_kills !== null &&
            this.state.eventGoal.max_kills !== null
        ) {
            return "Creature Kill";
        }
        if (
            this.state.eventGoal.total_crafts !== null &&
            this.state.eventGoal.max_crafts !== null
        ) {
            return "Item Crafting Amount";
        }
        if (
            this.state.eventGoal.total_enchants !== null &&
            this.state.eventGoal.max_enchants !== null
        ) {
            return "Enchanting Amount";
        }
        return "Unknown Event Step";
    };
    EventGoalsTab.prototype.setCurrentAmount = function () {
        if (this.state.eventGoal === null) {
            return;
        }
        if (
            this.state.eventGoal.total_kills !== null &&
            this.state.eventGoal.max_kills !== null
        ) {
            this.setState({
                player_amount: this.state.eventGoal.current_kills,
            });
        }
        if (
            this.state.eventGoal.total_crafts !== null &&
            this.state.eventGoal.max_crafts !== null
        ) {
            this.setState({
                player_amount: this.state.eventGoal.current_crafts,
            });
        }
        if (
            this.state.eventGoal.total_enchants !== null &&
            this.state.eventGoal.max_enchants !== null
        ) {
            this.setState({
                player_amount: this.state.eventGoal.current_enchants,
            });
        }
        return;
    };
    EventGoalsTab.prototype.render = function () {
        if (this.state.loading) {
            return React.createElement(LoadingProgressBar, null);
        }
        if (this.state.eventGoal === null) {
            return React.createElement(LoadingProgressBar, null);
        }
        return React.createElement(
            "div",
            null,
            React.createElement(
                "div",
                { className: "relative top-[-30px]" },
                React.createElement(OrangeProgressBar, {
                    primary_label:
                        "Event Goal - " + this.getTitleForProgressBar(),
                    secondary_label: this.getEventGoalLabel(),
                    percentage_filled: this.getOverAllProgress(),
                    height_override_class: "h-3",
                    text_override_class: "text-md",
                    push_down: true,
                }),
            ),
            React.createElement(
                "div",
                { className: "my-4 relative top-2" },
                React.createElement(
                    "p",
                    { className: "my-2" },
                    "Each phase is an objective that when completed will reward all players who participated with a piece of new gear in relation to the event goal.",
                ),
                React.createElement(
                    "p",
                    { className: "my-2" },
                    "Goals are created such that the more players, the faster you get the reward.",
                ),
                React.createElement(
                    "dl",
                    { className: "my-2" },
                    React.createElement("dt", null, "Gear Set Name"),
                    React.createElement(
                        "dd",
                        null,
                        React.createElement(
                            "a",
                            {
                                href:
                                    "/information/" +
                                    kebabCase(this.state.eventGoal.reward),
                                target: "_blank",
                            },
                            this.state.eventGoal.reward,
                            " ",
                            React.createElement("i", {
                                className: "fas fa-external-link-alt",
                            }),
                        ),
                    ),
                    React.createElement(
                        "dt",
                        null,
                        "With Legendary Unique Attached?",
                    ),
                    React.createElement(
                        "dd",
                        null,
                        this.state.eventGoal.should_be_unique ? "Yes" : "No",
                    ),
                    React.createElement("dt", null, "With Mythic Attached?"),
                    React.createElement(
                        "dd",
                        null,
                        this.state.eventGoal.should_be_mythic ? "Yes" : "No",
                    ),
                ),
                React.createElement(
                    "p",
                    { className: "my-2 font-bold" },
                    React.createElement(
                        "span",
                        { className: "text-orange-500 dark:text-orange-300" },
                        "Contribution required for reward:",
                    ),
                    " ",
                    formatNumber(this.state.eventGoal.amount_needed_for_reward),
                ),
                React.createElement(
                    "p",
                    { className: "my-2 font-bold" },
                    React.createElement(
                        "span",
                        { className: "text-orange-500 dark:text-orange-300" },
                        "Your current contribution:",
                    ),
                    " ",
                    formatNumber(this.state.player_amount),
                ),
            ),
            React.createElement(
                "div",
                {
                    className:
                        "max-h-[200px] overflow-y-scroll px-2 relative top-[-10px]",
                },
                this.buildProgressBars(),
            ),
        );
    };
    return EventGoalsTab;
})(React.Component);
export default EventGoalsTab;
//# sourceMappingURL=event-goals-tab.js.map
