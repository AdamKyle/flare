import React from "react";
import Ajax from "../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import LoadingProgressBar from "../components/ui/progress-bars/loading-progress-bar";
import {FieldProps, ProcessedEvent, SchedulerHelpers} from "@aldabil/react-scheduler/types";
import EventView from "../components/ui/scheduler/event-view";
import EventCalendar from "../components/ui/scheduler/calendar";
import EventSchedulerModal from "./components/event-scheduler-modal";
import CalendarState from "./types/calendar-state";

export default class Calendar extends React.Component<{}, CalendarState> {

    private updateScheduledEvents: any;

    constructor(props: {}) {
        super(props);

        this.state = {
            events: [],
            loading: true,
        }

        // @ts-ignore
        this.updateScheduledEvents = Echo.join('update-event-schedule');
    }

    componentDidMount() {
        (new Ajax).setRoute('calendar/events')
            .doAjaxCall('get', (result: AxiosResponse) => {
                this.setState({
                    events: result.data.events.map((event: any) => {
                        event.start = new Date(event.start);
                        event.end   = new Date(event.end);

                        return event;
                    }),
                    loading: false,
                })
            }, (error: AxiosError) => {
                console.error(error);
            });

        this.updateScheduledEvents.listen('Flare.Events.UpdateScheduledEvents', (event: any) => {
            // We have to do this for the calendar to update when a new event is created and added to the calendar.
            // If we don't the calendar does not properly update, calling forceUpdate, doesn't work.
            this.setState({
                loading: true
            }, () => {
                this.setState({
                    events: event.eventData.map((event: any) => {
                        event.start = new Date(event.start);
                        event.end   = new Date(event.end);

                        return event;
                    }),
                    loading: false,
                });
            });

        });
    }

    render() {
        if (this.state.loading) {
            return <LoadingProgressBar />
        }

        return <EventCalendar events={this.state.events}
                         view={'month'}
                         viewerExtraComponent={(fields: FieldProps[]|[], event: ProcessedEvent) =>
                             <EventView event={event} deleting={false} />
                         }
                         customEditor={(scheduler: SchedulerHelpers) =>  <EventSchedulerModal scheduler={scheduler} />}
                         can_edit={false}
        />
    }
}
