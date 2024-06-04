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
import Dialogue from "../../../../game/components/ui/dialogue/dialogue";
import ComponentLoading from "../../../../game/components/ui/loading/component-loading";
import LoadingProgressBar from "../../../../game/components/ui/progress-bars/loading-progress-bar";
import SuccessAlert from "../../../../game/components/ui/alerts/simple-alerts/success-alert";
import DangerAlert from "../../../../game/components/ui/alerts/simple-alerts/danger-alert";
import {
    buildValueLink,
    getRequirementKey,
    guideQuestLabelBuilder,
} from "../lib/guide-quest-label-builder";
import RequiredListItem from "../components/required-list-item";
import { questRewardKeys } from "../lib/guide-quests-rewards";
import RewardListItem from "../components/reward-list-item";
import TabLayout from "../components/tab-labout";
import InfoAlert from "../../../../game/components/ui/alerts/simple-alerts/info-alert";
import clsx from "clsx";
import GuideQuestAjax, { GUIDE_QUEST_ACTIONS } from "../ajax/guide-quest-ajax";
import { guideQuestServiceContainer } from "../container/guide-quest-container";
var EVENT_TYPE;
(function (EVENT_TYPE) {
    EVENT_TYPE[(EVENT_TYPE["WINTER_EVENT"] = 4)] = "WINTER_EVENT";
})(EVENT_TYPE || (EVENT_TYPE = {}));
var GuideQuest = (function (_super) {
    __extends(GuideQuest, _super);
    function GuideQuest(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            loading: true,
            action_loading: false,
            error_message: null,
            success_message: null,
            quest_data: null,
            can_hand_in: false,
            is_handing_in: false,
            completed_requirements: [],
        };
        _this.guideQuestAjax =
            guideQuestServiceContainer().fetch(GuideQuestAjax);
        return _this;
    }
    GuideQuest.prototype.componentDidMount = function () {
        this.guideQuestAjax.doGuideQuestAction(this, GUIDE_QUEST_ACTIONS.FETCH);
    };
    GuideQuest.prototype.buildTitle = function () {
        if (this.state.loading) {
            return "One moment ...";
        }
        if (this.state.quest_data === null) {
            return "Guide Quest";
        }
        return this.state.quest_data.name;
    };
    GuideQuest.prototype.closeMessage = function () {
        this.setState({
            success_message: null,
            error_message: null,
        });
    };
    GuideQuest.prototype.handInQuest = function () {
        this.guideQuestAjax.doGuideQuestAction(
            this,
            GUIDE_QUEST_ACTIONS.HAND_IN,
        );
    };
    GuideQuest.prototype.fetchRequiredKeys = function () {
        var _this = this;
        if (this.state.quest_data === null) {
            return ["UNKNOWN"];
        }
        return Object.keys(this.state.quest_data).filter(function (key) {
            if (_this.state.quest_data !== null) {
                return (
                    (key.startsWith("required_") ||
                        key.startsWith("secondary_")) &&
                    _this.state.quest_data[key] !== null
                );
            }
        });
    };
    GuideQuest.prototype.buildRequirementsList = function () {
        var _this = this;
        var requirementsList = [];
        this.fetchRequiredKeys().forEach(function (key) {
            if (_this.state.quest_data === null) {
                return [];
            }
            var label = guideQuestLabelBuilder(key, _this.state.quest_data);
            if (label !== null) {
                var requiredKey = getRequirementKey(key);
                var value = _this.state.quest_data[requiredKey];
                var completedRequirements =
                    _this.state.completed_requirements || [];
                var isFinished =
                    completedRequirements.includes(key) ||
                    completedRequirements.includes(requiredKey);
                requirementsList.push(
                    React.createElement(RequiredListItem, {
                        key: key,
                        label: label,
                        isFinished: isFinished,
                        requirement: buildValueLink(
                            value,
                            key,
                            _this.state.quest_data,
                        ),
                    }),
                );
            }
        });
        return requirementsList;
    };
    GuideQuest.prototype.buildRewardsItems = function () {
        var _this = this;
        var items = [];
        questRewardKeys().forEach(function (key) {
            if (_this.state.quest_data === null) {
                return [];
            }
            if (_this.state.quest_data[key] !== null) {
                var label = key
                    .split("_")
                    .map(function (word) {
                        return word.charAt(0).toUpperCase() + word.slice(1);
                    })
                    .join(" ");
                items.push(
                    React.createElement(RewardListItem, {
                        label: label,
                        value: _this.state.quest_data[key],
                    }),
                );
            }
        });
        return items;
    };
    GuideQuest.prototype.render = function () {
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.manage_modal,
                title: this.buildTitle() + " [GUIDE QUEST]",
                secondary_actions: {
                    secondary_button_label: "Hand in",
                    secondary_button_disabled: !this.state.can_hand_in,
                    handle_action: this.handInQuest.bind(this),
                },
                medium_modal: this.state.quest_data !== null,
                primary_button_disabled: this.state.action_loading,
            },
            this.state.loading && this.state.quest_data === null
                ? React.createElement(
                      "div",
                      { className: "p-5 mb-2" },
                      React.createElement(ComponentLoading, null),
                  )
                : this.state.quest_data === null
                  ? React.createElement(
                        "div",
                        {
                            className:
                                "my-4 text-orange-500 dark:text-orange-300",
                        },
                        React.createElement(
                            "p",
                            null,
                            "You have completed all the current Guide Quests. When new features are released there will be more Guide Quests for you to walk you through new features!",
                        ),
                    )
                  : React.createElement(
                        "div",
                        {
                            className:
                                "overflow-y-auto max-h-[450px] lg:max-h-none lg:overflow-visible",
                        },
                        React.createElement(
                            InfoAlert,
                            {
                                additional_css: clsx("my-4", {
                                    hidden:
                                        this.state.quest_data
                                            .only_during_event === null &&
                                        this.state.quest_data
                                            .unlock_at_level === null,
                                }),
                            },
                            React.createElement(
                                "p",
                                null,
                                'These types of Guide Quests only pop up during special events or when new features are unlocked at specific levels. You can continue your regular guide quests once you finish this one and any "child" quests that might folow after it.',
                            ),
                        ),
                        this.state.success_message !== null
                            ? React.createElement(
                                  SuccessAlert,
                                  { close_alert: this.closeMessage.bind(this) },
                                  this.state.success_message,
                              )
                            : null,
                        this.state.error_message !== null
                            ? React.createElement(
                                  DangerAlert,
                                  { close_alert: this.closeMessage.bind(this) },
                                  this.state.error_message,
                              )
                            : null,
                        React.createElement(
                            "div",
                            { className: "mt-2" },
                            React.createElement(
                                "div",
                                { className: "grid md:grid-cols-2 gap-2" },
                                React.createElement(
                                    "div",
                                    null,
                                    React.createElement(
                                        "h3",
                                        { className: "mb-2" },
                                        "Required to complete",
                                    ),
                                    React.createElement(
                                        "ul",
                                        {
                                            className:
                                                "my-4 list-disc ml-[18px]",
                                        },
                                        this.buildRequirementsList(),
                                    ),
                                ),
                                React.createElement("div", {
                                    className:
                                        "block md:hidden border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                                }),
                                React.createElement(
                                    "div",
                                    null,
                                    React.createElement(
                                        "h3",
                                        { className: "mb-2" },
                                        "Rewards",
                                    ),
                                    React.createElement(
                                        "ul",
                                        { className: "list-disc ml-[18px]" },
                                        this.buildRewardsItems(),
                                    ),
                                ),
                            ),
                        ),
                        React.createElement("div", {
                            className:
                                "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                        }),
                        this.state.quest_data.faction_points_per_kill !== null
                            ? React.createElement(
                                  "p",
                                  {
                                      className:
                                          "text-blue-700 dark:text-blue-400",
                                  },
                                  "You have been given an additional",
                                  " ",
                                  this.state.quest_data.faction_points_per_kill,
                                  " ",
                                  "Faction Points per kill for this quest.",
                              )
                            : null,
                        React.createElement(TabLayout, {
                            intro_text: this.state.quest_data.intro_text,
                            instructions: this.state.quest_data.instructions,
                            desktop_instructions:
                                this.state.quest_data.desktop_instructions,
                            mobile_instructions:
                                this.state.quest_data.mobile_instructions,
                            is_small: this.props.view_port < 1600,
                        }),
                        React.createElement(
                            "p",
                            { className: "mt-4 mb-4" },
                            "The Hand in button will become available when you meet the requirements. Unless exploration is running.",
                        ),
                        React.createElement(
                            "p",
                            { className: "mt-4 mb-4" },
                            "You can click the top right button in the header called Guide Quests to re-open this modal. You can also see previous Guide Quests by opening the top left menu, selecting Quest Log and then selecting Completed Guide Quests.",
                        ),
                        this.state.is_handing_in
                            ? React.createElement(LoadingProgressBar, null)
                            : null,
                    ),
        );
    };
    return GuideQuest;
})(React.Component);
export default GuideQuest;
//# sourceMappingURL=guide-quest.js.map
