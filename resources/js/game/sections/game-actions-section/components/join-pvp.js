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
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import DangerButton from "../../../components/ui/buttons/danger-button";
import Ajax from "../../../lib/ajax/ajax";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import { startCase } from "lodash";
import Select from "react-select";
var JoinPvp = (function (_super) {
    __extends(JoinPvp, _super);
    function JoinPvp(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            loading: false,
            attack_type: null,
        };
        return _this;
    }
    JoinPvp.prototype.attackTypes = function () {
        return [
            {
                label: "Attack",
                value: "attack",
            },
            {
                label: "Cast",
                value: "cast",
            },
            {
                label: "Attack and Cast",
                value: "attack_and_cast",
            },
            {
                label: "Cast and Attack",
                value: "cast_and_attack",
            },
            {
                label: "Defend",
                value: "defend",
            },
        ];
    };
    JoinPvp.prototype.setAttackType = function (data) {
        this.setState({
            attack_type: data.value !== "" ? data.value : null,
        });
    };
    JoinPvp.prototype.defaultAttackType = function () {
        if (this.state.attack_type !== null) {
            return {
                label: startCase(this.state.attack_type),
                value: this.state.attack_type,
            };
        }
        return {
            label: "Please select attack type",
            value: "",
        };
    };
    JoinPvp.prototype.joinPvp = function () {
        var _this = this;
        this.setState(
            {
                loading: true,
            },
            function () {
                new Ajax()
                    .setRoute("join-monthly-pvp/" + _this.props.character_id)
                    .setParameters({
                        attack_type: _this.state.attack_type,
                    })
                    .doAjaxCall(
                        "post",
                        function (response) {
                            _this.setState(
                                {
                                    loading: false,
                                },
                                function () {
                                    _this.props.manage_section();
                                },
                            );
                        },
                        function (error) {},
                    );
            },
        );
    };
    JoinPvp.prototype.render = function () {
        return React.createElement(
            "div",
            { className: "mt-2 md:ml-[120px]" },
            React.createElement(
                "div",
                { className: "mt-2 grid md:grid-cols-3 gap-2" },
                React.createElement(
                    "div",
                    { className: "md:cols-start-1 md:col-span-2" },
                    React.createElement(
                        "p",
                        { className: "mb-4" },
                        "Here you can choose to join in the monthly PVP event. It is suggested you read this",
                        React.createElement(
                            "a",
                            {
                                href: "/information/monthly-pvp-event",
                                target: "_blank",
                                className: "ml-2",
                            },
                            "Help Document",
                            " ",
                            React.createElement("i", {
                                className: "fas fa-external-link-alt",
                            }),
                        ),
                        " ",
                        "before continuing.",
                    ),
                    React.createElement(
                        "div",
                        null,
                        React.createElement(Select, {
                            onChange: this.setAttackType.bind(this),
                            options: this.attackTypes(),
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
                            value: this.defaultAttackType(),
                        }),
                    ),
                    this.state.loading
                        ? React.createElement(LoadingProgressBar, null)
                        : null,
                    React.createElement(PrimaryButton, {
                        button_label: "Join Tonight's Event!",
                        on_click: this.joinPvp.bind(this),
                        additional_css: "mt-4",
                        disabled: this.state.attack_type === null,
                    }),
                    React.createElement(DangerButton, {
                        button_label: "Close Section",
                        on_click: this.props.manage_section,
                        additional_css: "mt-4 md:mt-0 md:mt-4 ml-4",
                    }),
                ),
            ),
        );
    };
    return JoinPvp;
})(React.Component);
export default JoinPvp;
//# sourceMappingURL=join-pvp.js.map
