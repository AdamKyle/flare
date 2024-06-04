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
var RequiredListItem = (function (_super) {
    __extends(RequiredListItem, _super);
    function RequiredListItem(props) {
        return _super.call(this, props) || this;
    }
    RequiredListItem.prototype.render = function () {
        return React.createElement(
            "li",
            { className: "text-orange-600 dark:text-orange-400" },
            this.props.isFinished
                ? React.createElement("i", {
                      className:
                          "fas fa-check text-green-700 dark:text-green-500 mr-2",
                  })
                : null,
            this.props.label,
            ": ",
            this.props.requirement,
        );
    };
    return RequiredListItem;
})(React.Component);
export default RequiredListItem;
//# sourceMappingURL=required-list-item.js.map
