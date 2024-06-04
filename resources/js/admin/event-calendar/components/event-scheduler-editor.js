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
import PrimaryButton from "../../../game/components/ui/buttons/primary-button";
import { DialogActions } from "@mui/material";
import DangerButton from "../../../game/components/ui/buttons/danger-button";
import LoadingProgressBar from "../../../game/components/ui/progress-bars/loading-progress-bar";
import EventSchedulerForm from "./event-scheduler-form";
import Ajax from "../../../game/lib/ajax/ajax";
import format from "date-fns/format";
var EventSchedulerEditor = (function (_super) {
    __extends(EventSchedulerEditor, _super);
    function EventSchedulerEditor(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            form_data: null,
            error_message: null,
            is_saving: false,
        };
        return _this;
    }
    EventSchedulerEditor.prototype.saveEvent = function () {
        var _this = this;
        if (this.state.form_data === null) {
            return;
        }
        if (!this.state.form_data.hasOwnProperty("selected_start_date")) {
            this.setState({
                error_message: "Missing start date of the event.",
            });
            return;
        }
        if (!this.state.form_data.hasOwnProperty("selected_end_date")) {
            this.setState({
                error_message: "Missing end date of the event.",
            });
            return;
        }
        this.setState({
            is_saving: true,
        });
        var route = "admin/create-new-event";
        if (typeof this.props.scheduler.edited !== "undefined") {
            route = "admin/update-event/" + this.props.scheduler.edited.id;
        }
        var postData = {
            selected_event_type: this.state.form_data.selected_event_type,
            event_description: this.state.form_data.event_description,
            selected_raid: this.state.form_data.selected_raid,
            selected_start_date: format(
                this.state.form_data.selected_start_date,
                "yyyy/MM/dd HH:mm:ss",
            ).toString(),
            selected_end_date:
                this.state.form_data.selected_end_date !== null
                    ? format(
                          this.state.form_data.selected_end_date,
                          "yyyy/MM/dd HH:mm:ss",
                      ).toString()
                    : null,
        };
        new Ajax()
            .setRoute(route)
            .setParameters(postData)
            .doAjaxCall(
                "post",
                function (result) {
                    _this.props.scheduler.close();
                },
                function (error) {
                    if (typeof error.response !== "undefined") {
                        _this.setState({
                            error_message: error.response.data.message,
                            is_saving: false,
                        });
                    }
                    console.error(error);
                },
            );
    };
    EventSchedulerEditor.prototype.closeEventManagement = function () {
        this.props.scheduler.close();
    };
    EventSchedulerEditor.prototype.updateParentData = function (formData) {
        this.setState({
            form_data: formData,
        });
    };
    EventSchedulerEditor.prototype.render = function () {
        return React.createElement(
            "div",
            { className: "w-[500px] p-[1rem] dark:bg-gray-800" },
            React.createElement(
                "h4",
                { className: "my-4 font-bold text-blue-600" },
                "Manage Event",
            ),
            this.state.is_saving
                ? React.createElement(LoadingProgressBar, null)
                : null,
            this.props.is_loading
                ? React.createElement(
                      "div",
                      { className: "pb-6" },
                      React.createElement(LoadingProgressBar, null),
                  )
                : React.createElement(EventSchedulerForm, {
                      raids: this.props.raids,
                      event_data: this.props.scheduler.edited,
                      update_parent: this.updateParentData.bind(this),
                      event_types: this.props.event_types,
                  }),
            React.createElement(
                "div",
                { className: "absolute bottom-0 right-0" },
                React.createElement(
                    DialogActions,
                    null,
                    React.createElement(DangerButton, {
                        button_label: "Cancel",
                        on_click: this.closeEventManagement.bind(this),
                        disabled: this.props.is_loading || this.state.is_saving,
                    }),
                    React.createElement(PrimaryButton, {
                        button_label: "Save Event",
                        on_click: this.saveEvent.bind(this),
                        disabled: this.props.is_loading || this.state.is_saving,
                    }),
                ),
            ),
        );
    };
    return EventSchedulerEditor;
})(React.Component);
export default EventSchedulerEditor;
//# sourceMappingURL=event-scheduler-editor.js.map
