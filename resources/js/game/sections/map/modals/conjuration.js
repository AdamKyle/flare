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
import React, { Fragment } from "react";
import Dialogue from "../../../components/ui/dialogue/dialogue";
import Ajax from "../../../lib/ajax/ajax";
import ComponentLoading from "../../../components/ui/loading/component-loading";
import Select from "react-select";
import { formatNumber } from "../../../lib/game/format-number";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
var Conjuration = (function (_super) {
    __extends(Conjuration, _super);
    function Conjuration(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            loading: true,
            celestials: [],
            selected_celestial_id: null,
            error_message: "",
            conjuring: false,
        };
        return _this;
    }
    Conjuration.prototype.componentDidMount = function () {
        var _this = this;
        new Ajax()
            .setRoute("celestial-beings/" + this.props.character_id)
            .doAjaxCall(
                "get",
                function (response) {
                    _this.setState({
                        loading: false,
                        celestials: response.data.celestial_monsters,
                    });
                },
                function (error) {},
            );
    };
    Conjuration.prototype.conjure = function (privateConjure) {
        var _this = this;
        if (this.state.selected_celestial_id === null) {
            return this.setState({
                error_message: "Select a creature child, before doing that.",
            });
        }
        this.setState(
            {
                conjuring: true,
            },
            function () {
                new Ajax()
                    .setRoute("conjure/" + _this.props.character_id)
                    .setParameters({
                        monster_id: _this.state.selected_celestial_id,
                        type: privateConjure ? "private" : "public",
                    })
                    .doAjaxCall("post", function (result) {
                        _this.setState(
                            {
                                conjuring: false,
                            },
                            function () {
                                _this.props.handle_close();
                            },
                        );
                    });
            },
        );
    };
    Conjuration.prototype.setSelectedCelestial = function (data) {
        this.setState({
            selected_celestial_id: data.value,
        });
    };
    Conjuration.prototype.buildCelestialOptions = function () {
        return this.state.celestials.map(function (celestial) {
            return {
                label:
                    celestial.name +
                    ", Gold Cost: " +
                    formatNumber(celestial.gold_cost) +
                    " Gold Dust Cost: " +
                    formatNumber(celestial.gold_dust_cost),
                value: celestial.id,
            };
        });
    };
    Conjuration.prototype.getSelectedCelestial = function () {
        var _this = this;
        var selectedCelestial = this.state.celestials.filter(
            function (celestial) {
                if (_this.state.selected_celestial_id !== null) {
                    return celestial.id === _this.state.selected_celestial_id;
                }
            },
        );
        if (selectedCelestial.length > 0) {
            var celestial = selectedCelestial[0];
            return {
                label:
                    celestial.name +
                    ", Gold Cost: " +
                    formatNumber(celestial.gold_cost) +
                    " and Gold Dust Cost: " +
                    formatNumber(celestial.gold_dust_cost),
                value: celestial.id,
            };
        }
        return {
            label: "Please select celestial to conjure",
            value: 0,
        };
    };
    Conjuration.prototype.render = function () {
        var _this = this;
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.handle_close,
                title: this.props.title,
                secondary_actions: {
                    handle_action: function () {
                        return _this.conjure(false);
                    },
                    secondary_button_disabled:
                        this.state.selected_celestial_id === null,
                    secondary_button_label: "Conjure",
                },
                tertiary_actions: {
                    handle_action: function () {
                        return _this.conjure(true);
                    },
                    tertiary_button_label: "Private Conjure",
                    tertiary_button_disabled:
                        this.state.selected_celestial_id === null,
                },
            },
            this.state.loading
                ? React.createElement(
                      "div",
                      { className: "h-40" },
                      React.createElement(ComponentLoading, null),
                  )
                : this.state.celestials.length === 0
                  ? React.createElement(
                        Fragment,
                        null,
                        React.createElement(
                            "p",
                            null,
                            "Sorry Child this plane has no celestials for you to conjure.",
                        ),
                    )
                  : React.createElement(
                        Fragment,
                        null,
                        this.state.error_message !== ""
                            ? React.createElement(
                                  DangerAlert,
                                  null,
                                  this.state.error_message,
                              )
                            : null,
                        React.createElement(
                            "p",
                            { className: "mb-4" },
                            "For more info, see:",
                            " ",
                            React.createElement(
                                "a",
                                {
                                    href: "/information/celestials",
                                    target: "_blank",
                                },
                                "Celestials help docs.",
                                " ",
                                React.createElement("i", {
                                    className: "fas fa-external-link-alt",
                                }),
                            ),
                        ),
                        React.createElement(
                            "p",
                            { className: "mb-4" },
                            "Check server message section below for relevant details including location. Private conjurations will show you the location in server messages, public will show everyone as a global message. Celestials are first come first serve entities.",
                        ),
                        React.createElement(
                            "div",
                            { className: "flex items-center" },
                            React.createElement(
                                "label",
                                { className: "w-[100px]" },
                                "Celestials",
                            ),
                            React.createElement(
                                "div",
                                { className: "w-2/3" },
                                React.createElement(Select, {
                                    onChange:
                                        this.setSelectedCelestial.bind(this),
                                    options: this.buildCelestialOptions(),
                                    menuPosition: "absolute",
                                    menuPlacement: "bottom",
                                    styles: {
                                        menuPortal: function (base) {
                                            return __assign(
                                                __assign({}, base),
                                                {
                                                    zIndex: 9999,
                                                    color: "#000000",
                                                },
                                            );
                                        },
                                    },
                                    menuPortalTarget: document.body,
                                    value: this.getSelectedCelestial(),
                                }),
                            ),
                        ),
                        this.state.conjuring
                            ? React.createElement(LoadingProgressBar, null)
                            : null,
                    ),
        );
    };
    return Conjuration;
})(React.Component);
export default Conjuration;
//# sourceMappingURL=conjuration.js.map
