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
import Dialogue from "./dialogue";
import clsx from "clsx";
var HelpDialogue = (function (_super) {
    __extends(HelpDialogue, _super);
    function HelpDialogue(props) {
        return _super.call(this, props) || this;
    }
    HelpDialogue.prototype.render = function () {
        if (this.props.character === null) {
            return null;
        }
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.manage_modal,
                title: this.props.title,
            },
            React.createElement(
                "div",
                {
                    className: clsx({
                        "max-h-[450px] overflow-x-auto":
                            typeof this.props.no_scrolling === "undefined",
                    }),
                },
                this.props.children,
            ),
        );
    };
    return HelpDialogue;
})(React.Component);
export default HelpDialogue;
//# sourceMappingURL=help-dialogue.js.map
