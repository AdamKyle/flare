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
import Ajax from "../../../game/lib/ajax/ajax";
import LoadingProgressBar from "../../../game/components/ui/progress-bars/loading-progress-bar";
import EventView from "../../../game/components/ui/scheduler/event-view";
import EventCalendar from "../../../game/components/ui/scheduler/calendar";
var Calendar = (function (_super) {
    __extends(Calendar, _super);
    function Calendar(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            events: [],
            loading: true,
        };
        _this.updateScheduledEvents = Echo.join("update-event-schedule");
        return _this;
    }
    Calendar.prototype.color = function (name) {
        if (name === "Weekly Celestials") {
            return "#0891B2";
        }
        if (name === "Weekly Currency Drops") {
            return "#E11D48";
        }
        if (name === "Monthly PVP") {
            return "#2563EB";
        }
        return "#1976d2";
    };
    Calendar.prototype.componentDidMount = function () {
        var _this = this;
        new Ajax().setRoute("calendar/events").doAjaxCall(
            "get",
            function (result) {
                _this.setState({
                    events: result.data.events.map(function (event) {
                        event.start = new Date(event.start);
                        event.end = new Date(event.end);
                        event.color = event.currently_running
                            ? "#16a34a"
                            : _this.color(event.title);
                        return event;
                    }),
                    loading: false,
                });
            },
            function (error) {
                console.error(error);
            },
        );
        this.updateScheduledEvents.listen(
            "Flare.Events.UpdateScheduledEvents",
            function (event) {
                _this.setState(
                    {
                        loading: true,
                    },
                    function () {
                        _this.setState({
                            events: event.eventData.map(function (event) {
                                event.start = new Date(event.start);
                                event.end = new Date(event.end);
                                event.color = event.currently_running
                                    ? "#16a34a"
                                    : _this.color(event.title);
                                return event;
                            }),
                            loading: false,
                        });
                    },
                );
            },
        );
    };
    Calendar.prototype.render = function () {
        if (this.state.loading) {
            return React.createElement(LoadingProgressBar, null);
        }
        return React.createElement(EventCalendar, {
            events: this.state.events,
            view: "month",
            viewerExtraComponent: function (fields, event) {
                return React.createElement(EventView, {
                    event: event,
                    deleting: false,
                });
            },
            can_edit: false,
        });
    };
    return Calendar;
})(React.Component);
export default Calendar;
//# sourceMappingURL=calendar.js.map
