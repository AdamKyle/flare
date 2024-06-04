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
import Comparison from "./comparison";
import ItemNameColorationText from "../items/item-name/item-name-coloration-text";
import PrimaryOutlineButton from "../ui/buttons/primary-outline-button";
import SuccessOutlineButton from "../ui/buttons/success-outline-button";
import ExpandedComparison from "./expanded-comparison";
import { ItemType } from "../items/enums/item-type";
import WarningAlert from "../ui/alerts/simple-alerts/warning-alert";
import { startCase } from "lodash";
import { formatNumber } from "../../lib/game/format-number";
import clsx from "clsx";
import Item from "../items/item";
var twoHandedWeapons = [ItemType.STAVE, ItemType.BOW, ItemType.HAMMER];
var ItemComparison = (function (_super) {
    __extends(ItemComparison, _super);
    function ItemComparison(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            expanded_comparison_details: null,
            view_port: 0,
        };
        return _this;
    }
    ItemComparison.prototype.componentDidMount = function () {
        var _this = this;
        this.setState({
            view_port:
                window.innerWidth || document.documentElement.clientWidth,
        });
        window.addEventListener("resize", function () {
            _this.setState({
                view_port:
                    window.innerWidth || document.documentElement.clientWidth,
            });
        });
    };
    ItemComparison.prototype.componentDidUpdate = function (prevProps) {
        if (
            prevProps.is_showing_expanded_comparison &&
            !this.props.is_showing_expanded_comparison
        ) {
            this.setState({
                expanded_comparison_details: null,
            });
        }
    };
    ItemComparison.prototype.renderEquipButtons = function (
        isInline,
        comparisonItemType,
    ) {
        var _this = this;
        var singleHandedItems = [
            ItemType.FAN,
            ItemType.WEAPON,
            ItemType.MACE,
            ItemType.GUN,
            ItemType.SCRATCH_AWL,
            ItemType.SHIELD,
        ];
        if (comparisonItemType) {
            if (twoHandedWeapons.includes(comparisonItemType)) {
                return;
            }
        }
        var itemType = this.props.comparison_info.itemToEquip.type;
        if (singleHandedItems.includes(itemType)) {
            return React.createElement(
                "div",
                { className: "flex justify-center" },
                React.createElement(PrimaryOutlineButton, {
                    button_label: "Left Hand",
                    on_click: function () {
                        return _this.handleSecondaryAction("left-hand");
                    },
                }),
                React.createElement(PrimaryOutlineButton, {
                    button_label: "Right Hand",
                    on_click: function () {
                        return _this.handleSecondaryAction("right-hand");
                    },
                    additional_css: "ml-4",
                }),
            );
        }
        if (twoHandedWeapons.includes(itemType)) {
            return React.createElement(
                React.Fragment,
                null,
                isInline
                    ? React.createElement(
                          WarningAlert,
                          { additional_css: "my-4" },
                          "This is a two handed weapon, it will replace both hands when equipped.",
                      )
                    : null,
                React.createElement(
                    "div",
                    { className: "flex justify-center" },
                    React.createElement(PrimaryOutlineButton, {
                        button_label: "Left Hand",
                        on_click: function () {
                            return _this.handleSecondaryAction("left-hand");
                        },
                    }),
                    React.createElement(PrimaryOutlineButton, {
                        button_label: "Right Hand",
                        on_click: function () {
                            return _this.handleSecondaryAction("right-hand");
                        },
                        additional_css: "ml-4",
                    }),
                ),
            );
        }
        if (ItemType.RING === itemType) {
            return React.createElement(
                "div",
                { className: "flex justify-center" },
                React.createElement(PrimaryOutlineButton, {
                    button_label: "Ring One",
                    on_click: function () {
                        return _this.handleSecondaryAction("ring-one");
                    },
                }),
                React.createElement(PrimaryOutlineButton, {
                    button_label: "Ring Two",
                    on_click: function () {
                        return _this.handleSecondaryAction("ring-two");
                    },
                    additional_css: "ml-4",
                }),
            );
        }
        if (
            [ItemType.SPELL_DAMAGE, ItemType.SPELL_HEALING].includes(itemType)
        ) {
            return React.createElement(
                "div",
                { className: "flex justify-center" },
                React.createElement(PrimaryOutlineButton, {
                    button_label: "Spell One",
                    on_click: function () {
                        return _this.handleSecondaryAction("spell-one");
                    },
                }),
                React.createElement(PrimaryOutlineButton, {
                    button_label: "Spell Two",
                    on_click: function () {
                        return _this.handleSecondaryAction("spell-two");
                    },
                    additional_css: "ml-4",
                }),
            );
        }
        return React.createElement(
            "div",
            { className: "flex justify-center" },
            React.createElement(PrimaryOutlineButton, {
                button_label: "Equip",
                on_click: function () {
                    return _this.handleSecondaryAction(
                        _this.props.comparison_info.itemToEquip.type,
                    );
                },
            }),
            React.createElement(
                "div",
                { className: "ml-4 mt-2" },
                "This item has a default position of",
                " ",
                React.createElement(
                    "strong",
                    null,
                    startCase(
                        this.props.comparison_info.itemToEquip.default_position,
                    ),
                ),
                " ",
                "selected for you.",
            ),
        );
    };
    ItemComparison.prototype.showExpandedComparison = function (comparison) {
        var _this = this;
        this.setState(
            {
                expanded_comparison_details: comparison,
            },
            function () {
                _this.props.manage_show_expanded_comparison();
            },
        );
    };
    ItemComparison.prototype.renderExpandedComparison = function () {
        if (this.state.expanded_comparison_details == null) {
            return;
        }
        return React.createElement(ExpandedComparison, {
            comparison_details: this.state.expanded_comparison_details,
            mobile_data: {
                view_port: this.state.view_port,
                mobile_height_restriction: this.props.mobile_height_restriction,
            },
        });
    };
    ItemComparison.prototype.handleSecondaryAction = function (position) {
        if (typeof this.props.handle_replace_action == "undefined") {
            return;
        }
        this.props.handle_replace_action(position);
    };
    ItemComparison.prototype.renderSecondaryActionButton = function () {
        var _this = this;
        if (!this.props.replace_button_text) {
            return;
        }
        return React.createElement(
            "div",
            null,
            React.createElement(SuccessOutlineButton, {
                button_label: this.props.replace_button_text,
                on_click: function () {
                    return _this.handleSecondaryAction(
                        _this.props.comparison_info.details[0].position,
                    );
                },
            }),
        );
    };
    ItemComparison.prototype.shouldRenderPositionButtons = function () {
        return typeof this.props.handle_replace_action !== "undefined";
    };
    ItemComparison.prototype.shouldUseMobileHeightRestrictions = function (
        customWidth,
    ) {
        return (
            this.state.view_port < customWidth &&
            this.props.mobile_height_restriction
        );
    };
    ItemComparison.prototype.renderColumns = function () {
        var _this = this;
        return React.createElement(
            "div",
            {
                className: clsx({
                    "max-h-[375px] overflow-y-scroll":
                        this.shouldUseMobileHeightRestrictions(1500),
                    "max-h-[200px] overflow-y-scroll":
                        this.shouldUseMobileHeightRestrictions(800),
                }),
            },
            React.createElement(
                "div",
                {
                    className: clsx("my-4", {
                        hidden: this.props.mobile_height_restriction,
                    }),
                },
                "Looking to purchase:",
                " ",
                React.createElement(
                    "strong",
                    null,
                    this.props.comparison_info.itemToEquip.affix_name,
                ),
                ", below is your comparison data, if you were to equip this item in the equipped items slot. This fabulous item will only cost you:",
                " ",
                formatNumber(this.props.comparison_info.itemToEquip.cost),
                " ",
                "gold!",
            ),
            React.createElement(
                "div",
                { className: "grid md:grid-cols-2 gap-4" },
                React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "div",
                        { className: "flex justify-between" },
                        React.createElement(
                            "div",
                            null,
                            React.createElement(ItemNameColorationText, {
                                item: this.props.comparison_info.details[0],
                                custom_width: true,
                                additional_css: "mt-4",
                            }),
                        ),
                        React.createElement(
                            "div",
                            null,
                            React.createElement(
                                "span",
                                {
                                    className:
                                        "text-gray-600 dark:text-gray-200",
                                },
                                "(",
                                startCase(
                                    this.props.comparison_info.details[0].type.replace(
                                        "-",
                                        " ",
                                    ),
                                ),
                                ")",
                            ),
                        ),
                    ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
                    }),
                    React.createElement(Comparison, {
                        comparison: this.props.comparison_info.details[0],
                    }),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
                    }),
                    React.createElement(
                        "div",
                        { className: "flex items-center" },
                        React.createElement(
                            "div",
                            { className: "mr-2" },
                            React.createElement(PrimaryOutlineButton, {
                                button_label: "See Expanded Details",
                                on_click: function () {
                                    return _this.showExpandedComparison(
                                        _this.props.comparison_info.details[0],
                                    );
                                },
                            }),
                        ),
                        this.renderSecondaryActionButton(),
                    ),
                ),
                React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "div",
                        { className: "flex justify-between" },
                        React.createElement(
                            "div",
                            null,
                            React.createElement(ItemNameColorationText, {
                                item: this.props.comparison_info.details[1],
                                custom_width: true,
                                additional_css: "mt-4",
                            }),
                        ),
                        React.createElement(
                            "div",
                            null,
                            React.createElement(
                                "span",
                                {
                                    className:
                                        "text-gray-600 dark:text-gray-200",
                                },
                                "(",
                                startCase(
                                    this.props.comparison_info.details[1].type.replace(
                                        "-",
                                        " ",
                                    ),
                                ),
                                ")",
                            ),
                        ),
                    ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
                    }),
                    React.createElement(Comparison, {
                        comparison: this.props.comparison_info.details[1],
                    }),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
                    }),
                    React.createElement(
                        "div",
                        { className: "flex items-center" },
                        React.createElement(
                            "div",
                            { className: "mr-2" },
                            React.createElement(PrimaryOutlineButton, {
                                button_label: "See Expanded Details",
                                on_click: function () {
                                    return _this.showExpandedComparison(
                                        _this.props.comparison_info.details[1],
                                    );
                                },
                            }),
                        ),
                        this.renderSecondaryActionButton(),
                    ),
                ),
            ),
        );
    };
    ItemComparison.prototype.renderSingleComparison = function () {
        var _this = this;
        var nonArmourItems = [
            ItemType.FAN,
            ItemType.WEAPON,
            ItemType.MACE,
            ItemType.GUN,
            ItemType.SCRATCH_AWL,
            ItemType.SHIELD,
            ItemType.STAVE,
            ItemType.HAMMER,
            ItemType.BOW,
            ItemType.RING,
            ItemType.SPELL_HEALING,
            ItemType.SPELL_DAMAGE,
        ];
        return React.createElement(
            "div",
            { className: "mr-auto ml-auto w-full md:w-3/5" },
            React.createElement(
                "div",
                {
                    className: clsx("my-4", {
                        hidden: this.props.mobile_height_restriction,
                    }),
                },
                "Looking to purchase:",
                " ",
                React.createElement(
                    "strong",
                    null,
                    this.props.comparison_info.itemToEquip.affix_name,
                ),
                ", below is your comparison data, if you were to equip this item in the equipped items slot. This fabulous item will only cost you:",
                " ",
                formatNumber(this.props.comparison_info.itemToEquip.cost),
                " ",
                "gold!",
            ),
            React.createElement(
                "h3",
                null,
                React.createElement(ItemNameColorationText, {
                    item: this.props.comparison_info.details[0],
                    custom_width: true,
                }),
            ),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
            }),
            React.createElement(Comparison, {
                comparison: this.props.comparison_info.details[0],
            }),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
            }),
            React.createElement(
                "div",
                { className: "flex items-center" },
                React.createElement(
                    "div",
                    { className: "mr-2" },
                    React.createElement(PrimaryOutlineButton, {
                        button_label: "See Expanded Details",
                        on_click: function () {
                            return _this.showExpandedComparison(
                                _this.props.comparison_info.details[0],
                            );
                        },
                    }),
                ),
                this.renderSecondaryActionButton(),
                nonArmourItems.includes(
                    this.props.comparison_info.itemToEquip.type,
                ) && this.shouldRenderPositionButtons()
                    ? React.createElement(
                          React.Fragment,
                          null,
                          React.createElement(
                              "div",
                              {
                                  className: clsx("px-5", {
                                      hidden: twoHandedWeapons.includes(
                                          this.props.comparison_info.details[0]
                                              .type,
                                      ),
                                  }),
                              },
                              "Or (Select position)",
                          ),
                          this.renderEquipButtons(
                              false,
                              this.props.comparison_info.details[0].type,
                          ),
                      )
                    : null,
            ),
        );
    };
    ItemComparison.prototype.renderComparison = function () {
        if (this.props.comparison_info.details.length === 0) {
            if (this.props.mobile_height_restriction) {
                return React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "p",
                        { className: "my-4 italic text-center" },
                        "You have nothing equipped. Anything is better then nothing.",
                    ),
                    React.createElement(Item, {
                        item: this.props.comparison_info.itemToEquip,
                    }),
                );
            }
            return React.createElement(
                "div",
                { className: "w-full md:max-w-3/5 md:mr-auto md:ml-auto" },
                React.createElement(
                    "p",
                    { className: "my-4 text-center" },
                    "You don't have anything equipped in this slot. Why not buy and equip the",
                    " ",
                    React.createElement(
                        "strong",
                        null,
                        this.props.comparison_info.itemToEquip.affix_name,
                    ),
                    " ",
                    "for the low, low price of:",
                    " ",
                    formatNumber(this.props.comparison_info.itemToEquip.cost),
                    " ",
                    "gold?",
                ),
                React.createElement(Item, {
                    item: this.props.comparison_info.itemToEquip,
                }),
                React.createElement("div", {
                    className:
                        "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4 w-1/3 ml-auto mr-auto",
                }),
                this.renderEquipButtons(true),
            );
        }
        if (this.props.comparison_info.details.length > 1) {
            return this.renderColumns();
        }
        return this.renderSingleComparison();
    };
    ItemComparison.prototype.render = function () {
        return React.createElement(
            React.Fragment,
            null,
            this.state.expanded_comparison_details !== null
                ? this.renderExpandedComparison()
                : this.renderComparison(),
        );
    };
    return ItemComparison;
})(React.Component);
export default ItemComparison;
//# sourceMappingURL=item-comparison.js.map
