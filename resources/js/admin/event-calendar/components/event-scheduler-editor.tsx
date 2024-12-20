import React from "react";
import PrimaryButton from "../../../game/components/ui/buttons/primary-button";
import { DialogActions } from "@mui/material";
import DangerButton from "../../../game/components/ui/buttons/danger-button";
import LoadingProgressBar from "../../../game/components/ui/progress-bars/loading-progress-bar";
import EventSchedulerForm from "./event-scheduler-form";
import EventForm from "../types/deffinitions/components/event-form";
import EventSchedulerEditorProps from "../types/components/event-scheduler-editor-props";
import EventSchedulerEditorState from "../types/components/event-scheduler-editor-state";
import { format } from "date-fns";
import DangerAlert from "../../../game/components/ui/alerts/simple-alerts/danger-alert";
import { AxiosError, AxiosResponse } from "axios";
import Ajax from "../../../game/lib/ajax/ajax";

export default class EventSchedulerEditor extends React.Component<
    EventSchedulerEditorProps,
    EventSchedulerEditorState
> {
    constructor(props: EventSchedulerEditorProps) {
        super(props);

        this.state = {
            form_data: null,
            error_message: null,
            is_saving: false,
        };
    }

    saveEvent() {
        if (this.state.form_data === null) {
            return;
        }

        if (
            !Object.prototype.hasOwnProperty.call(
                this.state.form_data,
                "selected_start_date",
            )
        ) {
            this.setState({
                error_message: "Missing start date of the event.",
            });

            return;
        }

        if (
            !Object.prototype.hasOwnProperty.call(
                this.state.form_data,
                "selected_end_date",
            )
        ) {
            this.setState({
                error_message: "Missing end date of the event.",
            });

            return;
        }

        this.setState({
            is_saving: true,
        });

        let route = "admin/create-new-event";

        if (typeof this.props.scheduler.edited !== "undefined") {
            route = "admin/update-event/" + this.props.scheduler.edited.id;
        }

        const raidForYearlyEvent = this.state.form_data.raids_for_event.map(
            (raidForEvent) => {
                return {
                    selected_raid: raidForEvent.selected_raid,
                    start_date: format(
                        raidForEvent.start_date as Date,
                        "yyyy/MM/dd HH:mm:ss",
                    ).toString(),
                    end_date: format(
                        raidForEvent.end_date as Date,
                        "yyyy/MM/dd HH:mm:ss",
                    ).toString(),
                };
            },
        );

        const postData = {
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
            raids_for_event: raidForYearlyEvent,
        };

        new Ajax()
            .setRoute(route)
            .setParameters(postData)
            .doAjaxCall(
                "post",
                () => {
                    this.props.scheduler.close();
                },
                (error: AxiosError) => {
                    if (typeof error.response !== "undefined") {
                        const response: AxiosResponse = error.response;

                        this.setState({
                            error_message: response.data.message,
                            is_saving: false,
                        });
                    }

                    console.error(error);
                },
            );
    }

    closeEventManagement() {
        this.props.scheduler.close();
    }

    updateParentData(formData: EventForm) {
        this.setState({
            form_data: formData,
        });
    }

    render() {
        return (
            <div className="w-[500px] p-[1rem] dark:bg-gray-800">
                <h4 className="my-4 font-bold text-blue-600">Manage Event</h4>

                {this.state.is_saving ? <LoadingProgressBar /> : null}

                {this.props.is_loading ? (
                    <div className="pb-6">
                        <LoadingProgressBar />
                    </div>
                ) : (
                    <>
                        {this.state.form_data !== null &&
                        this.state.form_data?.error_message !== null ? (
                            <DangerAlert additional_css="my-4">
                                {this.state.form_data?.error_message}
                            </DangerAlert>
                        ) : null}

                        <EventSchedulerForm
                            raids={this.props.raids}
                            event_data={this.props.scheduler.edited}
                            update_parent={this.updateParentData.bind(this)}
                            event_types={this.props.event_types}
                            start_date={
                                this.props.scheduler.state.start.value as Date
                            }
                        />
                    </>
                )}

                <div className="absolute bottom-0 right-0">
                    <DialogActions>
                        <DangerButton
                            button_label={"Cancel"}
                            on_click={this.closeEventManagement.bind(this)}
                            disabled={
                                this.props.is_loading || this.state.is_saving
                            }
                        />
                        <PrimaryButton
                            button_label={"Save Event"}
                            on_click={this.saveEvent.bind(this)}
                            disabled={
                                this.props.is_loading ||
                                this.state.is_saving ||
                                (this.state.form_data !== null &&
                                    this.state.form_data?.error_message !==
                                        null)
                            }
                        />
                    </DialogActions>
                </div>
            </div>
        );
    }
}
