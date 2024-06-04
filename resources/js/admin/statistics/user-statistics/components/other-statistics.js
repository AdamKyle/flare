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
import Ajax from "../../../../game/lib/ajax/ajax";
import ComponentLoading from "../../../../game/components/ui/loading/component-loading";
import BasicCard from "../../../../game/components/ui/cards/basic-card";
import { formatNumber } from "../../../../game/lib/game/format-number";
var OtherStatistics = (function (_super) {
    __extends(OtherStatistics, _super);
    function OtherStatistics(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            data: null,
            loading: true,
        };
        return _this;
    }
    OtherStatistics.prototype.componentDidMount = function () {
        var _this = this;
        new Ajax().setRoute("admin/site-statistics/other-stats").doAjaxCall(
            "get",
            function (result) {
                _this.setState({
                    data: result.data,
                    loading: false,
                });
            },
            function (error) {
                console.error(error);
            },
        );
    };
    OtherStatistics.prototype.renderKingdomHolders = function () {
        var _this = this;
        var elements = [];
        var characterNames = Object.keys(this.state.data.kingdomHolders);
        characterNames.forEach(function (characterName) {
            elements.push(
                React.createElement(
                    Fragment,
                    null,
                    React.createElement("dd", null, characterName),
                    React.createElement(
                        "dt",
                        null,
                        _this.state.data.kingdomHolders[characterName],
                    ),
                ),
            );
        });
        return elements;
    };
    OtherStatistics.prototype.render = function () {
        if (this.state.loading) {
            return React.createElement(ComponentLoading, null);
        }
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                BasicCard,
                { additionalClasses: "mb-5" },
                React.createElement(
                    "h3",
                    { className: "mb-4" },
                    "Login Details",
                ),
                React.createElement(
                    "dl",
                    null,
                    React.createElement(
                        "dt",
                        null,
                        "Last Login count (5 Months):",
                    ),
                    React.createElement(
                        "dd",
                        null,
                        this.state.data.lastFiveMonthsLoggedInCount,
                    ),
                    React.createElement(
                        "dt",
                        null,
                        "Last Login count (Today):",
                    ),
                    React.createElement(
                        "dd",
                        null,
                        this.state.data.lastLoggedInCount,
                    ),
                    React.createElement("dt", null, "Never Logged in count:"),
                    React.createElement(
                        "dd",
                        null,
                        this.state.data.neverLoggedInCount,
                    ),
                    React.createElement("dt", null, "Accounts to be deleted:"),
                    React.createElement(
                        "dd",
                        null,
                        this.state.data.willBeDeletedCount,
                    ),
                ),
            ),
            React.createElement(
                "div",
                { className: "grid lg:grid-cols-2 gap-3 mb-5" },
                React.createElement(
                    BasicCard,
                    null,
                    React.createElement(
                        "h3",
                        { className: "mb-4" },
                        "Averages",
                    ),
                    React.createElement(
                        "dl",
                        null,
                        React.createElement(
                            "dt",
                            null,
                            "Average Character Level:",
                        ),
                        React.createElement(
                            "dd",
                            null,
                            this.state.data.averageCharacterLevel,
                        ),
                        React.createElement(
                            "dt",
                            null,
                            "Average Character Gold:",
                        ),
                        React.createElement(
                            "dd",
                            null,
                            this.state.data.averageCharacterGold,
                        ),
                        React.createElement(
                            "dt",
                            null,
                            "Average Quests Completed:",
                        ),
                        React.createElement(
                            "dd",
                            null,
                            this.state.data.averageRegularQuestsCompleted,
                        ),
                        React.createElement(
                            "dt",
                            null,
                            "Average Guide Quests Completed:",
                        ),
                        React.createElement(
                            "dd",
                            null,
                            this.state.data.averageGuideQuestsCompleted,
                        ),
                    ),
                ),
                React.createElement(
                    BasicCard,
                    null,
                    React.createElement(
                        "h3",
                        { className: "mb-4" },
                        "Highest Level and Richest Character",
                    ),
                    React.createElement(
                        "dl",
                        null,
                        React.createElement(
                            "dt",
                            null,
                            "Highest Level Character:",
                        ),
                        React.createElement(
                            "dd",
                            null,
                            this.state.data.highestLevelCharacter.name,
                            " ",
                            "(LV:",
                            " ",
                            this.state.data.highestLevelCharacter.level,
                            ")",
                        ),
                        React.createElement("dt", null, "Richest Character:"),
                        React.createElement(
                            "dd",
                            null,
                            this.state.data.richestCharacter.name,
                            " (Gold:",
                            " ",
                            formatNumber(this.state.data.richestCharacter.gold),
                            ")",
                        ),
                    ),
                ),
            ),
            React.createElement(
                BasicCard,
                { additionalClasses: "mb-5" },
                React.createElement(
                    "h3",
                    { className: "mb-4" },
                    "Kingdom Details",
                ),
                React.createElement(
                    "dl",
                    null,
                    React.createElement(
                        "dt",
                        null,
                        "Total Character Owned Kingdoms:",
                    ),
                    React.createElement(
                        "dd",
                        null,
                        this.state.data.characterKingdomCount,
                    ),
                    React.createElement("dt", null, "Total NPC Kingdoms:"),
                    React.createElement(
                        "dd",
                        null,
                        this.state.data.npcKingdomCount,
                    ),
                ),
            ),
            React.createElement(
                BasicCard,
                { additionalClasses: "mb-5" },
                React.createElement(
                    "h3",
                    { className: "mb-4" },
                    "Character Kingdom Count",
                ),
                React.createElement("dl", null, this.renderKingdomHolders()),
            ),
        );
    };
    return OtherStatistics;
})(React.Component);
export default OtherStatistics;
//# sourceMappingURL=other-statistics.js.map
