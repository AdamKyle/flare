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
import DangerAlert from "../../../game/components/ui/alerts/simple-alerts/danger-alert";
import LoadingProgressBar from "../../../game/components/ui/progress-bars/loading-progress-bar";
import { setHours, setMinutes } from "date-fns";
import Select from "react-select";
import Dialogue from "../../../game/components/ui/dialogue/dialogue";
import InfoAlert from "../../../game/components/ui/alerts/simple-alerts/info-alert";
import Ajax from "../../../game/lib/ajax/ajax";
import { DateTime } from "luxon";
import { cloneDeep } from "lodash";
var GenerateEventType = (function (_super) {
    __extends(GenerateEventType, _super);
    function GenerateEventType(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            action_in_progress: false,
            form_data: {
                selected_event_type: null,
                generate_every: null,
                selected_start_date: setHours(setMinutes(new Date(), 0), 9),
            },
            error_message: null,
        };
        _this.type_options = [
            {
                label: "Weekly Celestial Spawns",
                value: "0",
            },
            {
                label: "Monthly PVP",
                value: "1",
            },
            {
                label: "Weekly Currency Drops",
                value: "2",
            },
            {
                label: "Weekly Faction Loyalty Event",
                value: "9",
            },
        ];
        _this.generate_options = [
            {
                label: "Weekly",
                value: "weekly",
            },
            {
                label: "Monthly",
                value: "monthly",
            },
        ];
        return _this;
    }
    GenerateEventType.prototype.handleGenerate = function () {
        var _this = this;
        this.setState(
            {
                error_message: null,
                action_in_progress: true,
            },
            function () {
                if (!_this.isDataValid()) {
                    _this.setState({
                        error_message:
                            "No field can be left blank. Please make sure the form is filled out.",
                        action_in_progress: false,
                    });
                    return;
                }
                var ajax = new Ajax();
                var formData = cloneDeep(_this.state.form_data);
                var startDate = formData.selected_start_date.toString();
                formData.selected_start_date = DateTime.fromISO(
                    new Date(startDate).toISOString(),
                    {
                        zone: "utc",
                    },
                )
                    .setZone("America/Edmonton")
                    .toISO();
                ajax.setRoute("admin/create-multiple-events")
                    .setParameters(_this.state.form_data)
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.props.handle_close();
                        },
                        function (error) {
                            var response = null;
                            if (typeof error.response !== "undefined") {
                                response = error.response;
                                _this.setState({
                                    error_message: "Something went wrong.",
                                    action_in_progress: false,
                                });
                                console.error(response);
                            }
                        },
                    );
            },
        );
    };
    GenerateEventType.prototype.isDataValid = function () {
        var formData = this.state.form_data;
        return Object.values(formData).every(function (value) {
            return value !== null;
        });
    };
    GenerateEventType.prototype.setTypeOfEvent = function (data) {
        this.setState({
            form_data: __assign(__assign({}, this.state.form_data), {
                selected_event_type: parseInt(data.value),
            }),
        });
    };
    GenerateEventType.prototype.setStartDate = function (date) {
        this.setState({
            form_data: __assign(__assign({}, this.state.form_data), {
                selected_start_date: date,
            }),
        });
    };
    GenerateEventType.prototype.setGenerateEveryType = function (data) {
        this.setState({
            form_data: __assign(__assign({}, this.state.form_data), {
                generate_every: data.value,
            }),
        });
    };
    GenerateEventType.prototype.selectedEventTypeValue = function () {
        var _this = this;
        var foundType = this.type_options.filter(function (type) {
            return (
                parseInt(type.value) ===
                _this.state.form_data.selected_event_type
            );
        });
        if (foundType.length > 0) {
            return foundType;
        }
        return [{ label: "Please select event type", value: "" }];
    };
    GenerateEventType.prototype.selectedGenerateEveryType = function () {
        var _this = this;
        var foundGenerateType = this.generate_options.filter(function (item) {
            return item.value === _this.state.form_data.generate_every;
        });
        if (foundGenerateType.length > 0) {
            return foundGenerateType;
        }
        return [{ label: "Please select generate every", value: "" }];
    };
    GenerateEventType.prototype.render = function () {
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.handle_close,
                title: "Generate Event Type",
                primary_button_disabled: this.state.action_in_progress,
                secondary_actions: {
                    secondary_button_disabled: this.state.action_in_progress,
                    secondary_button_label: "Generate",
                    handle_action: this.handleGenerate.bind(this),
                },
                large_modal: true,
            },
            React.createElement(
                Fragment,
                null,
                this.state.error_message !== null
                    ? React.createElement(
                          DangerAlert,
                          { additional_css: "my-4" },
                          this.state.error_message,
                      )
                    : null,
                React.createElement(
                    "p",
                    { className: "my-4" },
                    "Here you can generate an event of a specif type that should take place weekly or monthly and generate a specific amount of events into the future based on the date and time you provide.",
                ),
                React.createElement(
                    "p",
                    { className: "my-4" },
                    "Events generated this way will generate for ever based on the amount you want generated from the date selected and based on how far out.",
                ),
                React.createElement(
                    "p",
                    { className: "my-4" },
                    "All events will generate a max of 5 out. For example if you select monthly, all events will generate out up to 5 months. At the half way mark to the last event for that type we will generate another 5 events after the last one that was generated.",
                ),
                React.createElement("div", {
                    className:
                        "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                }),
                React.createElement(
                    "div",
                    { className: "my-4" },
                    React.createElement(
                        "div",
                        { className: "grid lg:grid-cols-2 gap-2" },
                        React.createElement(
                            "div",
                            null,
                            React.createElement(
                                "div",
                                { className: "my-3 dark:text-gray-300" },
                                React.createElement(
                                    "strong",
                                    null,
                                    "Start Date (and time)",
                                ),
                            ),
                            "Missing Date Picker ....",
                        ),
                        React.createElement(
                            InfoAlert,
                            null,
                            React.createElement(
                                "p",
                                { className: "my-4" },
                                "Future events will use this date + the time that should pass, weekly or monthly and only last for 24 hours. For example, an event that starts on monday at noon will end on Tuesday at noon and repeat the following week.",
                            ),
                        ),
                    ),
                ),
                React.createElement(Select, {
                    onChange: this.setTypeOfEvent.bind(this),
                    options: this.type_options,
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
                    value: this.selectedEventTypeValue(),
                }),
                React.createElement(
                    "div",
                    { className: "my-4" },
                    React.createElement(Select, {
                        onChange: this.setGenerateEveryType.bind(this),
                        options: this.generate_options,
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
                        value: this.selectedGenerateEveryType(),
                    }),
                ),
                this.state.action_in_progress
                    ? React.createElement(LoadingProgressBar, null)
                    : null,
            ),
        );
    };
    return GenerateEventType;
})(React.Component);
export default GenerateEventType;
//# sourceMappingURL=generate-event-type.js.map
