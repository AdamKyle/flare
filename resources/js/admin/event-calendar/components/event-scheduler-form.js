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
import DateTimePicker from "react-datetime-picker";
import Select from "react-select";
import { setHours, setMinutes } from "date-fns";
import EventType from "../values/EventType";
import "react-datetime-picker/dist/DateTimePicker.css";
import "react-calendar/dist/Calendar.css";
import "react-clock/dist/Clock.css";
var EventSchedulerForm = (function (_super) {
    __extends(EventSchedulerForm, _super);
    function EventSchedulerForm(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            selected_event_type: null,
            event_description: "",
            selected_raid: null,
            selected_start_date: setHours(setMinutes(new Date(), 0), 9),
            selected_end_date: null,
        };
        return _this;
    }
    EventSchedulerForm.prototype.componentDidMount = function () {
        if (typeof this.props.event_data !== "undefined") {
            this.setState({
                selected_event_type: this.props.event_data.event_type,
                event_description: this.props.event_data.description,
                selected_raid: this.props.event_data.raid_id,
                selected_start_date: new Date(this.props.event_data.start),
                selected_end_date: new Date(this.props.event_data.end),
            });
            return;
        }
        var endDate = setHours(setMinutes(new Date(), 0), 9);
        endDate = new Date(endDate.setDate(endDate.getDate() + 1));
        this.setState({
            selected_end_date: endDate,
        });
    };
    EventSchedulerForm.prototype.setEventType = function (data) {
        var _this = this;
        if (data.value < 0) {
            return;
        }
        this.setState(
            {
                selected_event_type: data.value,
            },
            function () {
                _this.props.update_parent(_this.state);
            },
        );
    };
    EventSchedulerForm.prototype.setDescription = function (event) {
        var _this = this;
        this.setState(
            {
                event_description: event.target.value,
            },
            function () {
                _this.props.update_parent(_this.state);
            },
        );
    };
    EventSchedulerForm.prototype.setStartDate = function (value) {
        var _this = this;
        this.setState(
            {
                selected_start_date: value,
            },
            function () {
                _this.props.update_parent(_this.state);
            },
        );
    };
    EventSchedulerForm.prototype.setEndDate = function (value) {
        var _this = this;
        this.setState(
            {
                selected_end_date: value,
            },
            function () {
                _this.props.update_parent(_this.state);
            },
        );
    };
    EventSchedulerForm.prototype.setRaidEvent = function (data) {
        var _this = this;
        if (data.value === 0) {
            return;
        }
        this.setState(
            {
                selected_raid: data.value,
            },
            function () {
                _this.props.update_parent(_this.state);
            },
        );
    };
    EventSchedulerForm.prototype.optionsForEventType = function () {
        var types = this.props.event_types.map(function (eventType, index) {
            return {
                label: eventType,
                value: index,
            };
        });
        types.unshift({
            label: "Please select a type",
            value: -1,
        });
        return types;
    };
    EventSchedulerForm.prototype.optionsForRaids = function () {
        var raids = this.props.raids.map(function (raid) {
            return {
                label: raid.name,
                value: raid.id,
            };
        });
        raids.unshift({
            label: "Please Select a raid",
            value: 0,
        });
        return raids;
    };
    EventSchedulerForm.prototype.getSelectedEventType = function () {
        var _this = this;
        var foundValue = this.props.event_types.find(function (event, index) {
            return index === _this.state.selected_event_type;
        });
        if (typeof foundValue !== "undefined") {
            return [
                {
                    label: foundValue,
                    value: this.state.selected_event_type,
                },
            ];
        }
        return [
            {
                label: "Please select a type",
                value: -1,
            },
        ];
    };
    EventSchedulerForm.prototype.getSelectedRaid = function () {
        var _this = this;
        var foundRaid = this.props.raids.find(function (raid) {
            return raid.id === _this.state.selected_raid;
        });
        if (typeof foundRaid !== "undefined") {
            return [
                {
                    label: foundRaid.name,
                    value: foundRaid.id,
                },
            ];
        }
        return [
            {
                label: "Please select a raid",
                value: 0,
            },
        ];
    };
    EventSchedulerForm.prototype.getSelectedEventTypeName = function () {
        var _this = this;
        var foundValue = this.props.event_types.find(function (event, index) {
            return index === _this.state.selected_event_type;
        });
        if (typeof foundValue !== "undefined") {
            return foundValue;
        }
        return "";
    };
    EventSchedulerForm.prototype.filterPassedTime = function (time) {
        var currentDate = new Date();
        var selectedDate = new Date(time);
        return currentDate.getTime() < selectedDate.getTime();
    };
    EventSchedulerForm.prototype.filterEndDates = function (date) {
        return date > this.state.selected_start_date;
    };
    EventSchedulerForm.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            React.createElement(Select, {
                onChange: this.setEventType.bind(this),
                options: this.optionsForEventType(),
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
                value: this.getSelectedEventType(),
            }),
            EventType.is(EventType.RAID_EVENT, this.getSelectedEventTypeName())
                ? React.createElement(
                      "div",
                      { className: "my-4" },
                      React.createElement(Select, {
                          onChange: this.setRaidEvent.bind(this),
                          options: this.optionsForRaids(),
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
                          value: this.getSelectedRaid(),
                      }),
                  )
                : null,
            React.createElement(
                "div",
                { className: "grid md:grid-cols-2 gap-2" },
                React.createElement(
                    "div",
                    { className: "my-4" },
                    React.createElement(
                        "div",
                        { className: "my-3 dark:text-gray-300" },
                        React.createElement(
                            "strong",
                            null,
                            "Start Date (and time)",
                        ),
                    ),
                    React.createElement(DateTimePicker, {
                        onChange: this.setStartDate.bind(this),
                        value: this.state.selected_start_date,
                    }),
                ),
                React.createElement(
                    "div",
                    { className: "my-4" },
                    React.createElement(
                        "div",
                        { className: "my-3 dark:text-gray-300" },
                        React.createElement(
                            "strong",
                            null,
                            "End Date (and time)",
                        ),
                    ),
                    React.createElement(DateTimePicker, {
                        onChange: this.setEndDate.bind(this),
                        value: this.state.selected_end_date,
                    }),
                ),
            ),
            React.createElement(
                "div",
                { className: "mt-4 mb-8" },
                React.createElement(
                    "div",
                    { className: "my-3 dark:text-gray-300" },
                    React.createElement("strong", null, "Description"),
                ),
                React.createElement("textarea", {
                    rows: 5,
                    cols: 45,
                    onChange: this.setDescription.bind(this),
                    className: "border-2 border-gray-300 p-4",
                    value: this.state.event_description,
                }),
            ),
        );
    };
    return EventSchedulerForm;
})(React.Component);
export default EventSchedulerForm;
//# sourceMappingURL=event-scheduler-form.js.map
