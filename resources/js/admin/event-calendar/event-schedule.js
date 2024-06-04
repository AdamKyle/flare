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
import Calendar from "../../game/components/ui/scheduler/calendar";
import EventSchedulerEditor from "./components/event-scheduler-editor";
import Ajax from "../../game/lib/ajax/ajax";
import LoadingProgressBar from "../../game/components/ui/progress-bars/loading-progress-bar";
import EventView from "../../game/components/ui/scheduler/event-view";
import PrimaryButton from "../../game/components/ui/buttons/primary-button";
import GenerateEventType from "./modals/generate-event-type";
import { DateTime } from "luxon";
var EventSchedule = (function (_super) {
    __extends(EventSchedule, _super);
    function EventSchedule(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            events: [],
            raids: [],
            event_types: [],
            loading: true,
            deleting: false,
            show_generate_event_modal: false,
        };
        _this.updateScheduledEvents = Echo.join("update-event-schedule");
        return _this;
    }
    EventSchedule.prototype.componentDidMount = function () {
        var _this = this;
        new Ajax().setRoute("admin/event-calendar/fetch-events").doAjaxCall(
            "get",
            function (result) {
                _this.setState({
                    raids: result.data.raids,
                    events: result.data.events.map(function (event) {
                        event.start = DateTime.fromJSDate(new Date(event.start))
                            .toLocal()
                            .toJSDate();
                        event.end = DateTime.fromJSDate(new Date(event.end))
                            .toLocal()
                            .toJSDate();
                        event.color = event.currently_running
                            ? "#16a34a"
                            : _this.color(event.title);
                        return event;
                    }),
                    loading: false,
                    event_types: result.data.event_types,
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
    EventSchedule.prototype.deleteEvent = function (eventId) {
        var _this = this;
        return new Promise(function (resolve, reject) {
            _this.setState(
                {
                    deleting: true,
                },
                function () {
                    new Ajax()
                        .setRoute("admin/delete-event")
                        .setParameters({
                            event_id: eventId,
                        })
                        .doAjaxCall(
                            "post",
                            function (result) {
                                _this.setState(
                                    {
                                        deleting: false,
                                    },
                                    function () {
                                        resolve(result.data);
                                    },
                                );
                            },
                            function (error) {
                                console.error(error);
                                reject(error);
                            },
                        );
                },
            );
        });
    };
    EventSchedule.prototype.color = function (name) {
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
    EventSchedule.prototype.manageGenerateModal = function () {
        this.setState({
            show_generate_event_modal: !this.state.show_generate_event_modal,
        });
    };
    EventSchedule.prototype.render = function () {
        var _this = this;
        if (this.state.loading) {
            return React.createElement(LoadingProgressBar, null);
        }
        return React.createElement(
            "div",
            null,
            React.createElement(PrimaryButton, {
                button_label: "Generate Event Type",
                on_click: this.manageGenerateModal.bind(this),
                additional_css: "my-2",
            }),
            React.createElement(Calendar, {
                events: this.state.events,
                view: "month",
                customEditor: function (scheduler) {
                    return React.createElement(EventSchedulerEditor, {
                        scheduler: scheduler,
                        is_loading: _this.state.loading,
                        raids: _this.state.raids,
                        event_types: _this.state.event_types,
                    });
                },
                viewerExtraComponent: function (fields, event) {
                    return React.createElement(EventView, {
                        event: event,
                        deleting: _this.state.deleting,
                    });
                },
                onDelete: this.deleteEvent.bind(this),
                can_edit: true,
            }),
            this.state.show_generate_event_modal
                ? React.createElement(GenerateEventType, {
                      is_open: this.state.show_generate_event_modal,
                      handle_close: this.manageGenerateModal.bind(this),
                  })
                : null,
        );
    };
    return EventSchedule;
})(React.Component);
export default EventSchedule;
//# sourceMappingURL=event-schedule.js.map
