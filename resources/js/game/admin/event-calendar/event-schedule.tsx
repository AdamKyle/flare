import React, {Fragment} from "react";
import Calendar from "../../components/ui/scheduler/calendar";
import EventSchedulerEditor from "./components/event-scheduler-editor";
import {SchedulerHelpers, SchedulerRef} from "@aldabil/react-scheduler/types";
import Ajax from "../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import LoadingProgressBar from "../../components/ui/progress-bars/loading-progress-bar";

export default class EventSchedule extends React.Component<any, any> {

    private updateScheduledEvents: any;

    constructor(props: any) {
        super(props);

        this.state = {
            events: [],
            raids: [],
            loading: true,
            deleting: false,
        }

        // @ts-ignore
        this.updateScheduledEvents = Echo.join('update-event-schedule');
    }

    componentDidMount() {
        (new Ajax).setRoute('admin/event-calendar/fetch-events')
            .doAjaxCall('get', (result: AxiosResponse) => {
                this.setState({
                    raids: result.data.raids,
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

    deleteEvent(eventId: number) {
        this.setState({
            deleting: true,
        }, () => {
            (new Ajax).setRoute('admin/delete-event').setParameters({
                event_id: eventId,
            }).doAjaxCall('post',
                (result: AxiosResponse) => {
                    this.setState({
                        deleting: false,
                    });
                },
                (error: AxiosError) => {
                    console.error(error);
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
                             />
                         }
                         viewerExtraComponent={(fields: any[]|[], event: any) => {
                             return (
                                 <Fragment>
                                     <div className='my-2'>
                                         <p className='text-gray-600 text-sm italic'>All event start/end dates and times are in GMT-6 Timezone.</p>
                                     </div>
                                     {
                                         this.state.deleting ?
                                             <LoadingProgressBar />
                                         : null
                                     }
                                     <div className='my-4'>
                                         <p>{event.description}</p>
                                     </div>
                                 </Fragment>
                             );
                         }}
                         onDelete={this.deleteEvent.bind(this)}
        />
    }
}
