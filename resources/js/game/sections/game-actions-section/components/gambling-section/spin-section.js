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
import clsx from "clsx";
import SuccessButton from "../../../../components/ui/buttons/success-button";
import { renderIcons } from "./helpers/render-icons";
var SpinSection = (function (_super) {
    __extends(SpinSection, _super);
    function SpinSection(props) {
        return _super.call(this, props) || this;
    }
    SpinSection.prototype.renderIcons = function (index) {
        var icon = this.props.icons[index];
        return React.createElement(
            "div",
            { className: "text-center mb-10" },
            React.createElement("i", {
                className: icon.icon + " text-7xl",
                style: { color: icon.color },
            }),
            React.createElement("p", { className: "text-lg mt-2" }, icon.title),
        );
    };
    SpinSection.prototype.render = function () {
        return React.createElement(
            "div",
            {
                className: clsx("max-w-[450px] m-auto lg:mr-auto", {
                    "ml-[150px]": !this.props.is_small,
                }),
            },
            React.createElement(
                "div",
                { className: "max-h-[150px] overflow-hidden mt-4" },
                React.createElement(
                    "div",
                    { className: "grid grid-cols-3" },
                    React.createElement(
                        "div",
                        null,
                        renderIcons(
                            this.props.spinning_indexes[0],
                            this.props.icons,
                        ),
                    ),
                    React.createElement(
                        "div",
                        null,
                        renderIcons(
                            this.props.spinning_indexes[1],
                            this.props.icons,
                        ),
                    ),
                    React.createElement(
                        "div",
                        null,
                        renderIcons(
                            this.props.spinning_indexes[2],
                            this.props.icons,
                        ),
                    ),
                ),
            ),
            React.createElement(
                "div",
                { className: "text-center" },
                React.createElement(SuccessButton, {
                    button_label: "Spin",
                    on_click: this.props.spin_action,
                    disabled: true,
                }),
            ),
        );
    };
    return SpinSection;
})(React.Component);
export default SpinSection;
//# sourceMappingURL=spin-section.js.map
