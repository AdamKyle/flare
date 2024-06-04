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
var __assign =
    (this && this.__assign) ||
    function () {
        __assign =
            Object.assign ||
            function (t) {
                for (var s, i = 1, n = arguments.length; i < n; i++) {
                    s = arguments[i];
                    for (var p in s)
                        if (Object.prototype.hasOwnProperty.call(s, p))
                            t[p] = s[p];
                }
                return t;
            };
        return __assign.apply(this, arguments);
    };
import React from "react";
import Select from "react-select";
var SelectItemToCraft = (function (_super) {
    __extends(SelectItemToCraft, _super);
    function SelectItemToCraft(props) {
        return _super.call(this, props) || this;
    }
    SelectItemToCraft.prototype.render = function () {
        return React.createElement(Select, {
            onChange: this.props.set_item_to_craft,
            options: this.props.items,
            menuPosition: "absolute",
            menuPlacement: "bottom",
            styles: {
                menuPortal: function (base) {
                    return __assign(__assign({}, base), {
                        zIndex: 9999,
                        color: "#000000",
                    });
                },
            },
            menuPortalTarget: document.body,
            value: this.props.default_item,
        });
    };
    return SelectItemToCraft;
})(React.Component);
export default SelectItemToCraft;
//# sourceMappingURL=select-item-to-craft.js.map
