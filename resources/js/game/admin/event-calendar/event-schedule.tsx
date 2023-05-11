import React from "react";
import Calendar from "../../components/ui/scheduler/calendar";
import EventSchedulerEditor from "./components/event-scheduler-editor";
import {FieldProps, ProcessedEvent, SchedulerHelpers} from "@aldabil/react-scheduler/types";
import Ajax from "../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import LoadingProgressBar from "../../components/ui/progress-bars/loading-progress-bar";
import EventScheduleState from "./types/event-schedule-state";
import EventView from "../../components/ui/scheduler/event-view";

export default class EventSchedule extends React.Component<{}, EventScheduleState> {

    private updateScheduledEvents: any;

    constructor(props: {}) {
        super(props);

        this.state = {
            events: [],
            raids: [],
            event_types: [],
            loading: true,
            deleting: false,
        }

        // @ts-ignore
        this.updateScheduledEvents = Echo.join('update-event-schedule');
    }

    componentDidMount() {
        (new Ajax).setRoute('admin/event-calendar/fetch-events')
            .doAjaxCall('get', (result: AxiosResponse) => {
                console.log(result.data);
                this.setState({
                    raids: result.data.raids,
                    events: result.data.events.map((event: any) => {
                        event.start = new Date(event.start);
                        event.end   = new Date(event.end);

                        return event;
                    }),
                    loading: false,
                    event_types: result.data.event_types
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

    deleteEvent(eventId: number): Promise<string | number | void> {
        return new Promise((resolve, reject) => {
            this.setState({
                deleting: true,
            }, () => {
                (new Ajax).setRoute('admin/delete-event').setParameters({
                    event_id: eventId,
                }).doAjaxCall('post',
                    (result: AxiosResponse) => {
                        this.setState({
                            deleting: false,
                        }, () => {
                            resolve(result.data);
                        });
                    },
                    (error: AxiosError) => {
                        console.error(error);

                        reject(error);
                    });
            });
        });
    }

    render() {

        if (this.state.loading) {
            return <LoadingProgressBar />
        }

        return <Calendar events={this.state.events}
                         view={'month'}
                         customEditor={(scheduler: SchedulerHelpers) =>
                             <EventSchedulerEditor scheduler={scheduler}
                                                   is_loading={this.state.loading}
                                                   raids={this.state.raids}
                                                   event_types={this.state.event_types}
                             />
                         }
                         viewerExtraComponent={(fields: FieldProps[]|[], event: ProcessedEvent) =>
                             <EventView event={event} deleting={this.state.deleting} />
                         }
                         onDelete={this.deleteEvent.bind(this)}
                         can_edit={true}
        />
    }
}
