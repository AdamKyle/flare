import React from "react";
import Calendar from "../../components/ui/scheduler/calendar";
import EventSchedulerEditor from "./components/event-scheduler-editor";
import {
    FieldProps,
    ProcessedEvent,
    SchedulerHelpers,
} from "@aldabil/react-scheduler/types";
import Ajax from "../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import LoadingProgressBar from "../../components/ui/progress-bars/loading-progress-bar";
import EventScheduleState from "./types/event-schedule-state";
import EventView from "../../components/ui/scheduler/event-view";
import PrimaryButton from "../../components/ui/buttons/primary-button";
import GenerateEventType from "./modals/generate-event-type";

export default class EventSchedule extends React.Component<
    {},
    EventScheduleState
> {
    private updateScheduledEvents: any;

    constructor(props: {}) {
        super(props);

        this.state = {
            events: [],
            raids: [],
            event_types: [],
            loading: true,
            deleting: false,
            show_generate_event_modal: false,
        };

        // @ts-ignore
        this.updateScheduledEvents = Echo.join("update-event-schedule");
    }

    componentDidMount() {
        new Ajax().setRoute("admin/event-calendar/fetch-events").doAjaxCall(
            "get",
            (result: AxiosResponse) => {
                this.setState({
                    raids: result.data.raids,
                    events: result.data.events.map((event: ProcessedEvent) => {
                        event.start = new Date(event.start);
                        event.end = new Date(event.end);
                        event.color = event.currently_running
                            ? "#16a34a"
                            : this.color(event.title);

                        return event;
                    }),
                    loading: false,
                    event_types: result.data.event_types,
                });
            },
            (error: AxiosError) => {
                console.error(error);
            }
        );

        this.updateScheduledEvents.listen(
            "Flare.Events.UpdateScheduledEvents",
            (event: any) => {
                // We have to do this for the calendar to update when a new event is created and added to the calendar.
                // If we don't the calendar does not properly update, calling forceUpdate, doesn't work.
                this.setState(
                    {
                        loading: true,
                    },
                    () => {
                        this.setState({
                            events: event.eventData.map(
                                (event: ProcessedEvent) => {
                                    event.start = new Date(event.start);
                                    event.end = new Date(event.end);
                                    event.color = event.currently_running
                                        ? "#16a34a"
                                        : this.color(event.title);

                                    return event;
                                }
                            ),
                            loading: false,
                        });
                    }
                );
            }
        );
    }

    deleteEvent(eventId: number): Promise<string | number | void> {
        return new Promise((resolve, reject) => {
            this.setState(
                {
                    deleting: true,
                },
                () => {
                    new Ajax()
                        .setRoute("admin/delete-event")
                        .setParameters({
                            event_id: eventId,
                        })
                        .doAjaxCall(
                            "post",
                            (result: AxiosResponse) => {
                                this.setState(
                                    {
                                        deleting: false,
                                    },
                                    () => {
                                        resolve(result.data);
                                    }
                                );
                            },
                            (error: AxiosError) => {
                                console.error(error);

                                reject(error);
                            }
                        );
                }
            );
        });
    }

    color(name: string): string {
        console.log(name);
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
    }

    manageGenerateModal() {
        this.setState({
            show_generate_event_modal: !this.state.show_generate_event_modal,
        });
    }

    render() {
        if (this.state.loading) {
            return <LoadingProgressBar />;
        }

        return (
            <div>
                <PrimaryButton
                    button_label="Generate Event Type"
                    on_click={this.manageGenerateModal.bind(this)}
                    additional_css="my-2"
                />
                <Calendar
                    events={this.state.events}
                    view={"month"}
                    customEditor={(scheduler: SchedulerHelpers) => (
                        <EventSchedulerEditor
                            scheduler={scheduler}
                            is_loading={this.state.loading}
                            raids={this.state.raids}
                            event_types={this.state.event_types}
                        />
                    )}
                    viewerExtraComponent={(
                        fields: FieldProps[] | [],
                        event: ProcessedEvent
                    ) => (
                        <EventView
                            event={event}
                            deleting={this.state.deleting}
                        />
                    )}
                    onDelete={this.deleteEvent.bind(this)}
                    can_edit={true}
                />
                {this.state.show_generate_event_modal ? (
                    <GenerateEventType
                        is_open={this.state.show_generate_event_modal}
                        handle_close={this.manageGenerateModal.bind(this)}
                    />
                ) : null}
            </div>
        );
    }
}
