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
import { formatNumber } from "../../../../lib/game/format-number";
import OrangeButton from "../../../../components/ui/buttons/orange-button";
var InfoTab = (function (_super) {
    __extends(InfoTab, _super);
    function InfoTab(props) {
        return _super.call(this, props) || this;
    }
    InfoTab.prototype.render = function () {
        if (this.props.character === null) {
            return null;
        }
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                "div",
                { className: "grid md:grid-cols-2 gap-2" },
                React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "dl",
                        null,
                        React.createElement("dt", null, "Name:"),
                        React.createElement(
                            "dd",
                            null,
                            this.props.character.name,
                        ),
                        React.createElement("dt", null, "Race:"),
                        React.createElement(
                            "dd",
                            null,
                            this.props.character.race,
                        ),
                        React.createElement("dt", null, "Class:"),
                        React.createElement(
                            "dd",
                            null,
                            this.props.character.class,
                        ),
                        React.createElement("dt", null, "Level:"),
                        React.createElement(
                            "dd",
                            null,
                            this.props.character.level,
                            "/",
                            this.props.character.max_level,
                        ),
                    ),
                ),
                React.createElement("div", {
                    className:
                        "border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-3",
                }),
                React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "dl",
                        null,
                        React.createElement("dt", null, "Max Health:"),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.character.health),
                        ),
                        React.createElement("dt", null, "Total Attack:"),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.character.attack),
                        ),
                        React.createElement("dt", null, "Heal For:"),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.character.heal_for),
                        ),
                        React.createElement("dt", null, "AC:"),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.character.ac),
                        ),
                    ),
                ),
            ),
            React.createElement("div", {
                className:
                    "border-b-2 block border-b-gray-300 dark:border-b-gray-600 my-3",
            }),
            React.createElement(
                "div",
                {
                    className:
                        "flex flex-wrap justify-center gap-2 lg:flex-nowrap text-center",
                },
                React.createElement(
                    "div",
                    { className: "mt-4 w-full lg:w-auto" },
                    React.createElement(OrangeButton, {
                        button_label: "Show additional details",
                        on_click: this.props.manage_addition_data,
                    }),
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
                        "XP",
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
                        style: {
                            width:
                                (this.props.character.xp /
                                    this.props.character.xp_next) *
                                    100 +
                                "%",
                        },
                    }),
                ),
            ),
        );
    };
    return InfoTab;
})(React.Component);
export default InfoTab;
//# sourceMappingURL=info-tab.js.map
