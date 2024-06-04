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
import Dialogue from "../../../components/ui/dialogue/dialogue";
import InfoAlert from "../../../components/ui/alerts/simple-alerts/info-alert";
import BasicCard from "../../../components/ui/cards/basic-card";
import RenderAtonementDetails from "./components/render-atonement-details";
import RenderAtonementAdjustment from "./components/render-atonement-adjustment";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import SeerActions from "../../../components/npc-actions/seer-camp/ajax/seer-actions";
var RemoveGemComparison = (function (_super) {
    __extends(RemoveGemComparison, _super);
    function RemoveGemComparison(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            is_removing: false,
            error_message: null,
        };
        return _this;
    }
    RemoveGemComparison.prototype.findGemNameToRemove = function (gemId) {
        var gem = this.props.gems.find(function (gem) {
            return gem.gem_id === gemId;
        });
        if (typeof gem !== "undefined") {
            return gem;
        }
        return null;
    };
    RemoveGemComparison.prototype.removeGem = function (gemIdToRemove) {
        var _this = this;
        this.setState(
            {
                is_removing: true,
                error_message: null,
            },
            function () {
                SeerActions.removeGem(
                    _this,
                    _this.props.selected_item,
                    gemIdToRemove,
                );
            },
        );
    };
    RemoveGemComparison.prototype.removeAllGems = function () {
        var _this = this;
        this.setState(
            {
                is_removing: true,
                error_message: null,
            },
            function () {
                SeerActions.removeAllGems(_this, _this.props.selected_item);
            },
        );
    };
    RemoveGemComparison.prototype.renderOutGemComparison = function () {
        var _this = this;
        return this.props.comparison_data.map(function (comparison) {
            var _a;
            var atonements = comparison.comparisons.atonements;
            var elementalAtonment = comparison.comparisons.elemental_damage;
            return React.createElement(
                BasicCard,
                { additionalClasses: "my-4" },
                React.createElement(
                    "h3",
                    { className: "my-4" },
                    "When removing:",
                    " ",
                    React.createElement(
                        "span",
                        { className: "text-lime-600 dark:text-lime-500" },
                        (_a = _this.findGemNameToRemove(
                            comparison.gem_id_to_remove,
                        )) === null || _a === void 0
                            ? void 0
                            : _a.gem_name,
                    ),
                ),
                React.createElement(
                    "div",
                    { className: "grid lg:grid-cols-2 gap-2" },
                    React.createElement(
                        "div",
                        null,
                        React.createElement(RenderAtonementDetails, {
                            original_atonement: _this.props.original_atonement,
                            title: "Original Atonement",
                        }),
                        React.createElement(
                            "div",
                            { className: "my-4" },
                            React.createElement(
                                "h4",
                                { className: "my-2" },
                                "Original Elemental Atonement",
                            ),
                            React.createElement(
                                "dl",
                                null,
                                React.createElement(
                                    "dt",
                                    null,
                                    "Elemental Atonement",
                                ),
                                React.createElement(
                                    "dd",
                                    null,
                                    _this.props.original_atonement
                                        .elemental_damage.name,
                                ),
                                React.createElement(
                                    "dt",
                                    null,
                                    "Elemental Damage",
                                ),
                                React.createElement(
                                    "dd",
                                    null,
                                    (
                                        _this.props.original_atonement
                                            .elemental_damage.amount * 100
                                    ).toFixed(2),
                                    "%",
                                ),
                            ),
                        ),
                    ),
                    React.createElement(
                        "div",
                        null,
                        React.createElement(RenderAtonementAdjustment, {
                            atonement_for_comparison: atonements,
                            original_atonement: _this.props.original_atonement,
                        }),
                        React.createElement(
                            "div",
                            { className: "my-4" },
                            React.createElement(
                                "h4",
                                { className: "my-2" },
                                "Adjusted Elemental Atonement",
                            ),
                            React.createElement(
                                "dl",
                                null,
                                React.createElement(
                                    "dt",
                                    null,
                                    "Elemental Atonement",
                                ),
                                React.createElement(
                                    "dd",
                                    null,
                                    elementalAtonment.name,
                                ),
                                React.createElement(
                                    "dt",
                                    null,
                                    "Elemental Damage",
                                ),
                                React.createElement(
                                    "dd",
                                    null,
                                    (elementalAtonment.amount * 100).toFixed(2),
                                    "%",
                                ),
                            ),
                        ),
                    ),
                ),
                React.createElement(
                    "div",
                    { className: "my-4" },
                    React.createElement(PrimaryButton, {
                        button_label: "Remove Gem",
                        on_click: function () {
                            _this.removeGem(comparison.gem_id_to_remove);
                        },
                    }),
                ),
            );
        });
    };
    RemoveGemComparison.prototype.render = function () {
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.manage_modal,
                title: "Remove Gem(s) on: " + this.props.item_name,
                primary_button_disabled: false,
                secondary_actions: {
                    secondary_button_label: "Remove All Gems",
                    secondary_button_disabled: false,
                    handle_action: this.removeAllGems.bind(this),
                },
            },
            React.createElement(
                InfoAlert,
                null,
                React.createElement(
                    "strong",
                    null,
                    "Removing gems costs 10 Gold bars per gem.",
                ),
            ),
            React.createElement(
                "p",
                { className: "my-4" },
                "Below is the original atonement of the item as well as the adjusted changes after removing each gem. You may of course choose to remove all gems, at the cost of 10 Gold Bars x The number of gems attached.",
            ),
            React.createElement(
                "div",
                { className: "max-h-[350px] overflow-y-scroll mb-4" },
                this.renderOutGemComparison(),
            ),
            this.state.is_removing
                ? React.createElement(LoadingProgressBar, null)
                : null,
            this.state.error_message !== null
                ? React.createElement(
                      DangerAlert,
                      null,
                      this.state.error_message,
                  )
                : null,
        );
    };
    return RemoveGemComparison;
})(React.Component);
export default RemoveGemComparison;
//# sourceMappingURL=remove-gem-comparison.js.map
