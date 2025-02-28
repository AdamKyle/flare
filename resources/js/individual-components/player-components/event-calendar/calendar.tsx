import React from "react";
import Ajax from "../../../game/lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import LoadingProgressBar from "../../../game/components/ui/progress-bars/loading-progress-bar";
import { FieldProps, ProcessedEvent } from "@aldabil/react-scheduler/types";
import EventView from "../../../game/components/ui/scheduler/event-view";
import EventCalendar from "../../../game/components/ui/scheduler/calendar";
import CalendarState from "./types/calendar-state";

interface CalendarProps {
    in_game: boolean;
}

export default class Calendar extends React.Component<
    CalendarProps,
    CalendarState
> {
    private updateScheduledEvents: any;

    constructor(props: CalendarProps) {
        super(props);

        this.state = {
            events: [],
            loading: true,
        };

        if (this.props.in_game) {
            // @ts-ignore
            this.updateScheduledEvents = Echo.join("update-event-schedule");
        }
    }

    color(name: string): string {
        if (name === "Weekly Celestials") {
            return "#0891B2";
        }

        if (name === "Weekly Currency Drops") {
            return "#E11D48";
        }

        return "#1976d2";
    }

    componentDidMount() {
        let route = "calendar/events";

        if (!this.props.in_game) {
            route = "calendar/fetch-upcoming-events";
        }

        new Ajax().setRoute(route).doAjaxCall(
            "get",
            (result: AxiosResponse) => {
                this.setState({
                    events: result.data.events.map((event: ProcessedEvent) => {
                        event.start = new Date(event.start);
                        event.end = new Date(event.end);
                        event.color = event.currently_running
                            ? "#16a34a"
                            : this.color(event.title as string);

                        return event;
                    }),
                    loading: false,
                });
            },
            (error: AxiosError) => {
                console.error(error);
            },
        );

        if (!this.props.in_game) {
            return;
        }

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
                                        : this.color(event.title as string);

                                    return event;
                                },
                            ),
                            loading: false,
                        });
                    },
                );
            },
        );
    }

    render() {
        if (this.state.loading) {
            return <LoadingProgressBar />;
        }

        return (
            <EventCalendar
                events={this.state.events}
                view={"month"}
                viewerExtraComponent={(
                    fields: FieldProps[] | [],
                    event: ProcessedEvent,
                ) => <EventView event={event} deleting={false} />}
                can_edit={false}
            />
        );
    }
}
