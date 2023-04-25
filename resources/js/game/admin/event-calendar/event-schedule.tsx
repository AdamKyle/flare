import React from "react";
import Calendar from "../../components/ui/scheduler/calendar";
import EventSchedulerEditor from "./components/event-scheduler-editor";
import {SchedulerHelpers} from "@aldabil/react-scheduler/types";
import Ajax from "../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";

export default class EventSchedule extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            events: [{
                event_id: 1,
                title: "Raid event",
                start: new Date(new Date(new Date().setHours(9)).setMinutes(0)),
                end: new Date(new Date(new Date().setHours(10)).setMinutes(0)),
            }],
            raids: [],
            loading: true,
        }
    }

    componentDidMount() {
        (new Ajax).setRoute('admin/event-calendar/fetch-events')
            .doAjaxCall('get', (result: AxiosResponse) => {
                this.setState({
                    raids: result.data.raids,
                    loading: false,
                })
            }, (error: AxiosError) => {
                console.error(error);
            });
    }

    render() {
        return <Calendar events={this.state.events}
                         view={'month'}
                         customEditor={(scheduler: SchedulerHelpers) =>
                             <EventSchedulerEditor scheduler={scheduler}
                                                   is_loading={this.state.loading}
                                                   raids={this.state.raids
                             }/>
                         }
        />
    }
}
