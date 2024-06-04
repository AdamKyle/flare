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
import { formatNumber } from "../../lib/game/format-number";
import OrangeProgressBar from "../../components/ui/progress-bars/orange-progress-bar";
var CharacterTopSection = (function (_super) {
    __extends(CharacterTopSection, _super);
    function CharacterTopSection(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            hide_top_bar: true,
        };
        return _this;
    }
    CharacterTopSection.prototype.hideTopBar = function () {
        this.setState({
            hide_top_bar: !this.state.hide_top_bar,
        });
    };
    CharacterTopSection.prototype.componentDidMount = function () {};
    CharacterTopSection.prototype.componentDidUpdate = function (
        prevProps,
        prevState,
    ) {
        if (this.props.view_port >= 1600 && this.state.hide_top_bar) {
            this.setState({
                hide_top_bar: false,
            });
        }
    };
    CharacterTopSection.prototype.getXpPercentage = function () {
        var xpNext = this.props.character.xp_next;
        var currentXP = this.props.character.xp;
        var value = (currentXP / xpNext) * 100;
        return value > 100 ? 100 : value;
    };
    CharacterTopSection.prototype.abbreviateNumber = function (stat) {
        if (typeof stat === "undefined") {
            return 0;
        }
        var statNumber = parseInt(stat.toFixed(0));
        var symbol = ["", "k", "M", "B", "T", "Quad.", "Qunit."];
        var tier = (Math.log10(Math.abs(statNumber)) / 3) | 0;
        if (tier == 0) return statNumber;
        var suffix = symbol[tier];
        var scale = Math.pow(10, tier * 3);
        var scaled = statNumber / scale;
        return scaled.toFixed(1) + suffix;
    };
    CharacterTopSection.prototype.render = function () {
        if (this.props.character === null) {
            return null;
        }
        if (this.state.hide_top_bar && this.props.view_port < 1600) {
            return React.createElement(
                Fragment,
                null,
                React.createElement(
                    "div",
                    { className: "grid grid-cols-2" },
                    React.createElement(
                        "span",
                        null,
                        React.createElement(
                            "strong",
                            null,
                            "Character Details",
                        ),
                    ),
                    React.createElement(
                        "div",
                        {
                            className:
                                "text-right cursor-pointer text-blue-500",
                        },
                        React.createElement(
                            "button",
                            { onClick: this.hideTopBar.bind(this) },
                            React.createElement("i", {
                                className: "fas fa-plus-circle",
                            }),
                        ),
                    ),
                ),
                React.createElement(
                    "div",
                    { className: "relative top-[24px]" },
                    React.createElement(
                        "div",
                        { className: "flex justify-between mb-1" },
                        React.createElement(
                            "span",
                            {
                                className:
                                    "font-medium text-orange-700 dark:text-white text-xs",
                            },
                            "XP (Current Level: ",
                            this.props.character.level,
                            "/",
                            this.props.character.max_level,
                            ")",
                        ),
                        React.createElement(
                            "span",
                            {
                                className:
                                    "text-xs font-medium text-orange-700 dark:text-white",
                            },
                            formatNumber(this.props.character.xp),
                            "/",
                            formatNumber(this.props.character.xp_next),
                        ),
                    ),
                    React.createElement(
                        "div",
                        {
                            className:
                                "w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700",
                        },
                        React.createElement("div", {
                            className: "bg-orange-600 h-1.5 rounded-full",
                            style: { width: this.getXpPercentage() + "%" },
                        }),
                    ),
                ),
            );
        }
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                "div",
                {
                    className:
                        "text-right cursor-pointer text-red-500 block lg:hidden",
                },
                React.createElement(
                    "button",
                    { onClick: this.hideTopBar.bind(this) },
                    React.createElement("i", {
                        className: "fas fa-minus-circle",
                    }),
                ),
            ),
            React.createElement(
                "div",
                { className: "grid md:grid-cols-4" },
                React.createElement(
                    "span",
                    { className: "mb-2 sm:mb-0" },
                    React.createElement("strong", null, "Name"),
                    ": ",
                    this.props.character.name,
                ),
                React.createElement(
                    "span",
                    { className: "mb-2 sm:mb-0" },
                    React.createElement("strong", null, "Race"),
                    ":",
                    " ",
                    React.createElement(
                        "a",
                        {
                            href:
                                "/information/race/" +
                                this.props.character.race_id,
                            target: "_blank",
                        },
                        this.props.character.race,
                    ),
                ),
                React.createElement(
                    "span",
                    { className: "mb-2 sm:mb-0" },
                    React.createElement("strong", null, "Class"),
                    ":",
                    " ",
                    React.createElement(
                        "a",
                        {
                            href:
                                "/information/class/" +
                                this.props.character.class_id,
                            target: "_blank",
                        },
                        this.props.character.class,
                    ),
                ),
                React.createElement(
                    "span",
                    { className: "mb-2 sm:mb-0" },
                    React.createElement("strong", null, "Gold"),
                    ": ",
                    this.props.character.gold,
                ),
            ),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 hidden sm:block",
            }),
            React.createElement(
                "div",
                { className: "grid md:grid-cols-3" },
                React.createElement(
                    "span",
                    { className: "mb-2 sm:mb-0" },
                    React.createElement("strong", null, "Gold Dust"),
                    ":",
                    " ",
                    this.props.character.gold_dust,
                ),
                React.createElement(
                    "span",
                    { className: "mb-2 sm:mb-0" },
                    React.createElement("strong", null, "Crystal Shards"),
                    ":",
                    " ",
                    this.props.character.shards,
                ),
                React.createElement(
                    "span",
                    { className: "mb-2 sm:mb-0" },
                    React.createElement("strong", null, "Copper Coins"),
                    ":",
                    " ",
                    this.props.character.copper_coins,
                ),
            ),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 hidden sm:block",
            }),
            React.createElement(
                "div",
                { className: "grid md:grid-cols-4" },
                React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "div",
                        { className: "py-1" },
                        React.createElement("strong", null, "Level"),
                        ": ",
                        this.props.character.level,
                        "/",
                        this.props.character.max_level,
                    ),
                    React.createElement(
                        "div",
                        { className: "py-1" },
                        React.createElement("strong", null, "AC"),
                        ":",
                        " ",
                        this.abbreviateNumber(this.props.character.ac),
                    ),
                    React.createElement(
                        "div",
                        { className: "py-1" },
                        React.createElement("strong", null, "Attack"),
                        ":",
                        " ",
                        this.abbreviateNumber(this.props.character.attack),
                    ),
                    React.createElement(
                        "div",
                        { className: "py-1" },
                        React.createElement("strong", null, "Health"),
                        ":",
                        " ",
                        this.props.character.is_dead
                            ? React.createElement(
                                  Fragment,
                                  null,
                                  React.createElement(
                                      "span",
                                      {
                                          className:
                                              "text-red-600 dark:text-red-400",
                                      },
                                      "0 [DEAD]",
                                  ),
                              )
                            : this.abbreviateNumber(
                                  this.props.character.health,
                              ),
                    ),
                ),
                React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "div",
                        { className: "py-1" },
                        React.createElement("strong", null, "Strength"),
                        ":",
                        " ",
                        this.abbreviateNumber(this.props.character.str_modded),
                    ),
                    React.createElement(
                        "div",
                        { className: "py-1" },
                        React.createElement("strong", null, "Durability"),
                        ":",
                        " ",
                        this.abbreviateNumber(this.props.character.dur_modded),
                    ),
                    React.createElement(
                        "div",
                        { className: "py-1" },
                        React.createElement("strong", null, "Dexterity"),
                        ":",
                        " ",
                        this.abbreviateNumber(this.props.character.dex_modded),
                    ),
                ),
                React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "div",
                        { className: "py-1" },
                        React.createElement("strong", null, "Intelligence"),
                        ":",
                        " ",
                        this.abbreviateNumber(this.props.character.int_modded),
                    ),
                    React.createElement(
                        "div",
                        { className: "py-1" },
                        React.createElement("strong", null, "Charisma"),
                        ":",
                        " ",
                        this.abbreviateNumber(this.props.character.chr_modded),
                    ),
                    React.createElement(
                        "div",
                        { className: "py-1" },
                        React.createElement("strong", null, "Focus"),
                        ":",
                        " ",
                        this.abbreviateNumber(
                            this.props.character.focus_modded,
                        ),
                    ),
                ),
                React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "div",
                        { className: "py-1" },
                        React.createElement("strong", null, "Agility"),
                        ":",
                        " ",
                        this.abbreviateNumber(this.props.character.agi_modded),
                    ),
                ),
            ),
            React.createElement(OrangeProgressBar, {
                primary_label: "XP",
                secondary_label:
                    formatNumber(this.props.character.xp) +
                    "/" +
                    formatNumber(this.props.character.xp_next),
                percentage_filled: this.getXpPercentage(),
                push_down: true,
            }),
        );
    };
    return CharacterTopSection;
})(React.Component);
export default CharacterTopSection;
//# sourceMappingURL=character-top-section.js.map
