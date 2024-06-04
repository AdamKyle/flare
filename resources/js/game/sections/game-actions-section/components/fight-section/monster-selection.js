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
import PrimaryButton from "../../../../components/ui/buttons/primary-button";
import DangerButton from "../../../../components/ui/buttons/danger-button";
var MonsterSelection = (function (_super) {
    __extends(MonsterSelection, _super);
    function MonsterSelection(props) {
        return _super.call(this, props) || this;
    }
    MonsterSelection.prototype.render = function () {
        return React.createElement(
            "div",
            { className: "mt-4 lg:mt-2 lg:ml-[120px]" },
            React.createElement(
                "div",
                { className: "lg:grid lg:grid-cols-3 lg:gap-2" },
                React.createElement(
                    "div",
                    { className: "lg:cols-start-1 lg:col-span-2" },
                    React.createElement(Select, {
                        onChange: this.props.set_monster_to_fight,
                        options: this.props.monsters,
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
                        value: this.props.default_monster,
                    }),
                ),
                React.createElement(
                    "div",
                    {
                        className:
                            "text-center mt-4 lg:mt-0 lg:text-left lg:cols-start-3 lg:cols-end-3",
                    },
                    React.createElement(PrimaryButton, {
                        button_label: "Attack",
                        on_click: this.props.attack,
                        disabled: this.props.is_attack_disabled,
                    }),
                    typeof this.props.close_monster_section !== "undefined"
                        ? React.createElement(DangerButton, {
                              button_label: "Close",
                              on_click: this.props.close_monster_section,
                              additional_css: "ml-4",
                          })
                        : null,
                ),
            ),
        );
    };
    return MonsterSelection;
})(React.Component);
export default MonsterSelection;
//# sourceMappingURL=monster-selection.js.map
