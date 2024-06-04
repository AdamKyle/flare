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
import DangerButton from "../../../../../ui/buttons/danger-button";
import { startCase } from "lodash";
import Ajax from "../../../../../../lib/ajax/ajax";
import LoadingProgressBar from "../../../../../ui/progress-bars/loading-progress-bar";
import ItemNameColorationText from "../../../../../items/item-name/item-name-coloration-text";
var StatBreakDown = (function (_super) {
    __extends(StatBreakDown, _super);
    function StatBreakDown(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            error_message: null,
            is_loading: true,
            details: null,
        };
        return _this;
    }
    StatBreakDown.prototype.componentDidMount = function () {
        var _this = this;
        this.setState(
            {
                error_message: "",
            },
            function () {
                if (_this.props.character === null) {
                    return;
                }
                new Ajax()
                    .setRoute(
                        "character-sheet/" +
                            _this.props.character_id +
                            "/stat-break-down",
                    )
                    .setParameters({
                        stat_type: _this.props.type,
                    })
                    .doAjaxCall(
                        "get",
                        function (response) {
                            _this.setState({
                                is_loading: false,
                                details: response.data.break_down,
                            });
                        },
                        function (error) {
                            _this.setState({ is_loading: false });
                            if (typeof error.response !== "undefined") {
                                _this.setState({
                                    error_message: error.response.data.mmessage,
                                });
                            }
                        },
                    );
            },
        );
    };
    StatBreakDown.prototype.titelizeType = function () {
        return startCase(this.props.type.replace("-", " "));
    };
    StatBreakDown.prototype.renderItemListEffects = function () {
        var _this = this;
        if (this.state.details === null) {
            return;
        }
        return this.state.details.items_equipped.map(function (equippedItem) {
            return React.createElement(
                "li",
                null,
                React.createElement(ItemNameColorationText, {
                    item: equippedItem.item_details,
                    custom_width: false,
                }),
                " ",
                React.createElement(
                    "span",
                    { className: "text-green-700 dark:text-green-500" },
                    "(+",
                    (equippedItem.item_base_stat * 100).toFixed(2),
                    "%)",
                ),
                equippedItem.attached_affixes.length > 0
                    ? React.createElement(
                          "ul",
                          {
                              className:
                                  "ps-5 mt-2 space-y-1 list-disc list-inside",
                          },
                          _this.renderAttachedAffixes(
                              equippedItem.attached_affixes,
                          ),
                      )
                    : null,
                equippedItem.item_holy_stacks_applied > 0
                    ? React.createElement(
                          "ul",
                          {
                              className:
                                  "ps-5 mt-2 space-y-1 list-disc list-inside",
                          },
                          React.createElement(
                              "li",
                              {
                                  className:
                                      "text-slate-700 dark:text-slate-300",
                              },
                              "You have applied:",
                              " ",
                              equippedItem.item_holy_stacks_applied,
                              " of:",
                              " ",
                              equippedItem.item_holy_stacks_applied,
                              " Holy Stacks, which gives you a bonus of:",
                              " ",
                              (equippedItem.total_stat_increase * 100).toFixed(
                                  2,
                              ),
                              "% to ",
                              _this.titelizeType(),
                          ),
                      )
                    : null,
            );
        });
    };
    StatBreakDown.prototype.renderBoonIncreaseAllStatsEffects = function () {
        if (this.state.details === null) {
            return;
        }
        if (this.state.details.boon_details.increases_all_stats.length <= 0) {
            return;
        }
        return this.state.details.boon_details.increases_all_stats.map(
            function (boonIncreaseAllStats) {
                return React.createElement(
                    "li",
                    null,
                    React.createElement(ItemNameColorationText, {
                        item: boonIncreaseAllStats.item_details,
                        custom_width: false,
                    }),
                    " ",
                    React.createElement(
                        "span",
                        { className: "text-green-700 dark:text-green-500" },
                        "(+",
                        (boonIncreaseAllStats.increase_amount * 100).toFixed(2),
                        "%)",
                    ),
                );
            },
        );
    };
    StatBreakDown.prototype.renderBoonIncreaseSpecificStatEffects =
        function () {
            if (this.state.details === null) {
                return;
            }
            if (
                this.state.details.boon_details.increases_single_stat.length <=
                0
            ) {
                return null;
            }
            return this.state.details.boon_details.increases_single_stat.map(
                function (boonIncreaseAllStats) {
                    return React.createElement(
                        "li",
                        null,
                        React.createElement(ItemNameColorationText, {
                            item: boonIncreaseAllStats.item_details,
                            custom_width: false,
                        }),
                        " ",
                        React.createElement(
                            "span",
                            { className: "text-green-700 dark:text-green-500" },
                            "(+",
                            (
                                boonIncreaseAllStats.increase_amount * 100
                            ).toFixed(2),
                            "%)",
                        ),
                    );
                },
            );
        };
    StatBreakDown.prototype.renderAncestralItemSkill = function () {
        if (this.state.details === null) {
            return;
        }
        return this.state.details.ancestral_item_skill_data.map(
            function (ancestralItemSkill) {
                return React.createElement(
                    "li",
                    null,
                    React.createElement(
                        "span",
                        { className: "text-orange-600 dark:text-orange-300" },
                        ancestralItemSkill.name,
                    ),
                    " ",
                    React.createElement(
                        "span",
                        { className: "text-green-700 dark:text-green-500" },
                        "(+",
                        (ancestralItemSkill.increase_amount * 100).toFixed(2),
                        "%)",
                    ),
                );
            },
        );
    };
    StatBreakDown.prototype.renderClassSpecialtiesStatIncrease = function () {
        if (this.state.details === null) {
            return;
        }
        if (this.state.details.class_specialties === null) {
            return null;
        }
        return this.state.details.class_specialties.map(
            function (classSpecialty) {
                return React.createElement(
                    "li",
                    null,
                    React.createElement(
                        "span",
                        { className: "text-sky-600 dark:text-sky-500" },
                        classSpecialty.name,
                    ),
                    " ",
                    React.createElement(
                        "span",
                        { className: "text-green-700 dark:text-green-500" },
                        "(+",
                        (classSpecialty.amount * 100).toFixed(2),
                        "%)",
                    ),
                );
            },
        );
    };
    StatBreakDown.prototype.renderAttachedAffixes = function (attachedAffixes) {
        var _this = this;
        return attachedAffixes.map(function (attachedAffix) {
            return React.createElement(
                "li",
                null,
                React.createElement(
                    "span",
                    { className: "text-slate-700 dark:text-slate-400" },
                    attachedAffix.affix_name,
                ),
                " ",
                React.createElement(
                    "span",
                    { className: "text-green-700 dark:text-green-500" },
                    "(+",
                    (attachedAffix[_this.props.type + "_mod"] * 100).toFixed(2),
                    "%);",
                ),
            );
        });
    };
    StatBreakDown.prototype.render = function () {
        if (this.state.loading || this.state.details === null) {
            return React.createElement(LoadingProgressBar, null);
        }
        return React.createElement(
            "div",
            null,
            React.createElement(
                "div",
                { className: "flex justify-between" },
                React.createElement(
                    "div",
                    { className: "flex items-center" },
                    React.createElement(
                        "h3",
                        { className: "mr-2" },
                        startCase(this.props.type.replace("-", " ")),
                    ),
                    React.createElement(
                        "span",
                        { className: "text-gray-700 dark:text-gray-400" },
                        "(Raw: ",
                        this.state.details.base_value,
                        ")",
                    ),
                ),
                React.createElement(DangerButton, {
                    button_label: "Close",
                    on_click: this.props.close_section,
                }),
            ),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
            }),
            React.createElement(
                "div",
                { className: "grid md:grid-cols-2 gap-2" },
                React.createElement(
                    "div",
                    null,
                    React.createElement("h4", null, "Equipped Modifiers"),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
                    }),
                    this.state.details.items_equipped.length > 0
                        ? React.createElement(
                              "ol",
                              {
                                  className:
                                      "space-y-4 text-gray-500 list-decimal list-inside dark:text-gray-400",
                              },
                              this.renderItemListEffects(),
                          )
                        : React.createElement(
                              "p",
                              null,
                              "You have nothing equipped.",
                          ),
                ),
                React.createElement("div", {
                    className:
                        "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2 block md:hidden",
                }),
                React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "h4",
                        null,
                        "Boons that increases all stats",
                    ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
                    }),
                    this.state.details.boon_details !== null
                        ? React.createElement(
                              "ol",
                              {
                                  className:
                                      "space-y-4 text-gray-500 list-decimal list-inside dark:text-gray-400",
                              },
                              this.renderBoonIncreaseAllStatsEffects(),
                          )
                        : React.createElement(
                              "p",
                              null,
                              "There are no boons applied that effect this specific stat.",
                          ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4",
                    }),
                    React.createElement(
                        "h4",
                        null,
                        "Boons that increase: ",
                        this.titelizeType(),
                    ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
                    }),
                    this.state.details.boon_details !== null
                        ? this.state.details.boon_details.hasOwnProperty(
                              "increases_single_stat",
                          )
                            ? React.createElement(
                                  "ul",
                                  {
                                      className:
                                          "space-y-4 text-gray-500 list-disc list-inside dark:text-gray-400",
                                  },
                                  this.renderBoonIncreaseSpecificStatEffects(),
                              )
                            : React.createElement(
                                  "p",
                                  null,
                                  "There are no boons applied that effect this specific stat.",
                              )
                        : React.createElement(
                              "p",
                              null,
                              "There are no boons applied that effect this specific stat.",
                          ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4",
                    }),
                    React.createElement(
                        "h4",
                        null,
                        " ",
                        "Equipped Class Specials That Raise:",
                        " ",
                        this.titelizeType(),
                    ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
                    }),
                    this.state.details.class_specialties !== null
                        ? React.createElement(
                              "ul",
                              {
                                  className:
                                      "space-y-4 text-gray-500 list-disc list-inside dark:text-gray-400",
                              },
                              this.renderClassSpecialtiesStatIncrease(),
                          )
                        : React.createElement(
                              "p",
                              null,
                              "There are no class specials equipped that effect this stat.",
                          ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4",
                    }),
                    React.createElement(
                        "h4",
                        null,
                        " ",
                        "Ancestral Item Skills That Raise:",
                        " ",
                        this.titelizeType(),
                    ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
                    }),
                    this.state.details.ancestral_item_skill_data !== null
                        ? React.createElement(
                              "ul",
                              {
                                  className:
                                      "space-y-4 text-gray-500 list-disc list-inside dark:text-gray-400",
                              },
                              this.renderAncestralItemSkill(),
                          )
                        : React.createElement(
                              "p",
                              null,
                              "There are no Ancestral Item Skills that effect this stat.",
                          ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4",
                    }),
                    React.createElement(
                        "h4",
                        null,
                        " Map Reduction To: ",
                        this.titelizeType(),
                    ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
                    }),
                    this.state.details.map_reduction !== null
                        ? React.createElement(
                              "ul",
                              {
                                  className:
                                      "space-y-4 text-gray-500 list-disc list-inside dark:text-gray-400",
                              },
                              React.createElement(
                                  "li",
                                  null,
                                  React.createElement(
                                      "span",
                                      {
                                          className:
                                              "text-slate-700 dark:text-slate-400",
                                      },
                                      this.state.details.map_reduction.map_name,
                                  ),
                                  " ",
                                  React.createElement(
                                      "span",
                                      {
                                          className:
                                              "text-red-700 darmk:text-red-500",
                                      },
                                      "(-",
                                      (
                                          this.state.details.map_reduction
                                              .reduction_amount * 100
                                      ).toFixed(2),
                                      "%)",
                                  ),
                              ),
                          )
                        : React.createElement(
                              "p",
                              null,
                              "There are no map reductions applied to this stat.",
                          ),
                ),
            ),
        );
    };
    return StatBreakDown;
})(React.Component);
export default StatBreakDown;
//# sourceMappingURL=stat-break-down.js.map
