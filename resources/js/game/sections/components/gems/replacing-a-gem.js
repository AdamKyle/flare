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
import BasicCard from "../../../components/ui/cards/basic-card";
import GemComparisonDetails from "./gem-comparison-details";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import clsx from "clsx";
import AtonementComparison from "./atonement-comparison";
var ReplacingAGem = (function (_super) {
    __extends(ReplacingAGem, _super);
    function ReplacingAGem(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            show_replacement_comparison: false,
            gem_name_to_replace: null,
        };
        return _this;
    }
    ReplacingAGem.prototype.replaceGem = function (gemName) {
        this.setState({
            show_replacement_comparison: true,
            gem_name_to_replace: gemName,
        });
    };
    ReplacingAGem.prototype.displayCards = function () {
        var _this = this;
        return this.props.when_replacing.map(function (gemComparisonDetails) {
            var gemYouHave = _this.props.gems_you_have.filter(function (gem) {
                return gem.id === gemComparisonDetails.gem_you_have_id;
            })[0];
            return React.createElement(
                BasicCard,
                { additionalClasses: "my-4" },
                React.createElement(
                    "div",
                    { className: "grid md:grid-cols-2 gap-2" },
                    React.createElement(
                        "div",
                        null,
                        React.createElement(
                            "h3",
                            { className: "my-4" },
                            React.createElement(
                                "span",
                                {
                                    className:
                                        "text-lime-600 dark:text-lime-500",
                                },
                                gemComparisonDetails.name,
                            ),
                            " ",
                            "(When Replacing)",
                        ),
                        React.createElement(GemComparisonDetails, {
                            gem: gemComparisonDetails,
                        }),
                    ),
                    React.createElement(
                        "div",
                        null,
                        React.createElement(
                            "h3",
                            { className: "my-4" },
                            React.createElement(
                                "span",
                                {
                                    className:
                                        "text-lime-600 dark:text-lime-500",
                                },
                                gemYouHave.name,
                            ),
                            " ",
                            "(Currently Socketed)",
                        ),
                        React.createElement(
                            Fragment,
                            null,
                            React.createElement(
                                "dl",
                                null,
                                React.createElement("dt", null, "Tier"),
                                React.createElement(
                                    "dd",
                                    null,
                                    gemYouHave.tier,
                                ),
                                React.createElement(
                                    "dt",
                                    null,
                                    gemYouHave.primary_atonement_name +
                                        " Atonement: ",
                                ),
                                React.createElement(
                                    "dd",
                                    null,
                                    (
                                        gemYouHave.primary_atonement_amount *
                                        100
                                    ).toFixed(0),
                                    "%",
                                ),
                                React.createElement(
                                    "dt",
                                    null,
                                    gemYouHave.secondary_atonement_name +
                                        " Atonement: ",
                                ),
                                React.createElement(
                                    "dd",
                                    null,
                                    (
                                        gemYouHave.secondary_atonement_amount *
                                        100
                                    ).toFixed(0),
                                    "%",
                                ),
                                React.createElement(
                                    "dt",
                                    null,
                                    gemYouHave.tertiary_atonement_name +
                                        " Atonement: ",
                                ),
                                React.createElement(
                                    "dd",
                                    null,
                                    (
                                        gemYouHave.tertiary_atonement_amount *
                                        100
                                    ).toFixed(0),
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
                        button_label: "Replace",
                        on_click: function () {
                            _this.replaceGem(gemYouHave.name);
                        },
                        disabled: _this.props.action_disabled,
                    }),
                ),
            );
        });
    };
    ReplacingAGem.prototype.render = function () {
        var _this = this;
        return React.createElement(
            "div",
            null,
            React.createElement(
                "p",
                { className: "my-4" },
                "Each card below will detail the comparison of replacing the gem at that slot. The cost is the same as attaching a gem to an empty slot.",
            ),
            React.createElement(
                "div",
                {
                    className: clsx({
                        "max-h-[350px] overflow-y-scroll":
                            this.props.when_replacing.length > 2,
                    }),
                },
                this.displayCards(),
            ),
            this.props.when_replacing.length <= 0
                ? React.createElement(
                      "div",
                      {
                          className:
                              "text-center text-orange-t00 dark:text-orange-500 my-4",
                      },
                      React.createElement(
                          "p",
                          null,
                          "No gems socketed. Anything is better then nothing.",
                      ),
                  )
                : null,
            this.state.show_replacement_comparison &&
                this.state.gem_name_to_replace !== null
                ? React.createElement(AtonementComparison, {
                      is_open: true,
                      manage_modal: function () {
                          _this.setState({
                              show_replacement_comparison: false,
                              gem_name_to_replace: null,
                          });
                      },
                      trading_with_seer: false,
                      original_atonement: this.props.original_atonement,
                      if_replacing: this.props.if_replacing,
                      gem_name: this.state.gem_name_to_replace,
                      update_parent: this.props.update_parent,
                      selected_gem: this.props.selected_gem,
                      selected_item: this.props.selected_item,
                      manage_parent_modal: this.props.manage_parent_modal,
                      character_id: this.props.character_id,
                  })
                : null,
        );
    };
    return ReplacingAGem;
})(React.Component);
export default ReplacingAGem;
//# sourceMappingURL=replacing-a-gem.js.map
