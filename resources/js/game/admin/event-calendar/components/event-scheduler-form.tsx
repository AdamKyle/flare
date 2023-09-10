import React, { Fragment } from "react";
import Select from "react-select";
import DatePicker from "react-datepicker";
import { setHours, setMinutes } from "date-fns";
import EventSchedulerFormState from "../types/components/event-scheduler-form-state";
import EventSchedulerFormProps from "../types/components/event-scheduler-form-props";
import EventType from "../values/EventType";

export default class EventSchedulerForm extends React.Component<
    EventSchedulerFormProps,
    EventSchedulerFormState
> {
    constructor(props: EventSchedulerFormProps) {
        super(props);

        this.state = {
            selected_event_type: null,
            event_description: "",
            selected_raid: null,
            selected_start_date: setHours(setMinutes(new Date(), 0), 9),
            selected_end_date: null,
        };
    }

    componentDidMount() {
        if (typeof this.props.event_data !== "undefined") {
            this.setState({
                selected_event_type: this.props.event_data.event_type,
                event_description: this.props.event_data.description,
                selected_raid: this.props.event_data.raid_id,
                selected_start_date: new Date(this.props.event_data.start),
                selected_end_date: new Date(this.props.event_data.end),
            });

            return;
        }

        let endDate = setHours(setMinutes(new Date(), 0), 9);

        endDate = new Date(endDate.setDate(endDate.getDate() + 1));

        this.setState({
            selected_end_date: endDate,
        });
    }

    setEventType(data: any) {
        if (data.value < 0) {
            return;
        }

        this.setState(
            {
                selected_event_type: data.value,
            },
            () => {
                this.props.update_parent(this.state);
            }
        );
    }

    setDescription(event: React.ChangeEvent<HTMLTextAreaElement>) {
        this.setState(
            {
                event_description: event.target.value,
            },
            () => {
                this.props.update_parent(this.state);
            }
        );
    }

    setStartDate(date: Date) {
        this.setState(
            {
                selected_start_date: date,
            },
            () => {
                this.props.update_parent(this.state);
            }
        );
    }

    setEndDate(date: Date) {
        this.setState(
            {
                selected_end_date: date,
            },
            () => {
                this.props.update_parent(this.state);
            }
        );
    }

    setRaidEvent(data: any) {
        if (data.value === 0) {
            return;
        }

        this.setState(
            {
                selected_raid: data.value,
            },
            () => {
                this.props.update_parent(this.state);
            }
        );
    }

    optionsForEventType() {
        const types = this.props.event_types.map(
            (eventType: string, index: number) => {
                return {
                    label: eventType,
                    value: index,
                };
            }
        );

        types.unshift({
            label: "Please select a type",
            value: -1,
        });

        return types;
    }

    optionsForRaids() {
        const raids = this.props.raids.map((raid: any) => {
            return {
                label: raid.name,
                value: raid.id,
            };
        });

        raids.unshift({
            label: "Please Select a raid",
            value: 0,
        });

        return raids;
    }

    getSelectedEventType() {
        const foundValue: string | undefined = this.props.event_types.find(
            (event: string, index: number) => {
                return index === this.state.selected_event_type;
            }
        );

        if (typeof foundValue !== "undefined") {
            return [
                {
                    label: foundValue,
                    value: this.state.selected_event_type,
                },
            ];
        }

        return [
            {
                label: "Please select a type",
                value: -1,
            },
        ];
    }

    getSelectedRaid() {
        const foundRaid = this.props.raids.find(
            (raid: any) => raid.id === this.state.selected_raid
        );

        if (typeof foundRaid !== "undefined") {
            return [
                {
                    label: foundRaid.name,
                    value: foundRaid.id,
                },
            ];
        }

        return [
            {
                label: "Please select a raid",
                value: 0,
            },
        ];
    }

    getSelectedEventTypeName(): string {
        const foundValue: string | undefined = this.props.event_types.find(
            (event: string, index: number) => {
                return index === this.state.selected_event_type;
            }
        );

        if (typeof foundValue !== "undefined") {
            return foundValue;
        }

        return "";
    }

    filterPassedTime(time: any) {
        const currentDate = new Date();
        const selectedDate = new Date(time);

        return currentDate.getTime() < selectedDate.getTime();
    }

    filterEndDates(date: Date) {
        return date > this.state.selected_start_date;
    }

    render() {
        return (
            <Fragment>
                <Select
                    onChange={this.setEventType.bind(this)}
                    options={this.optionsForEventType()}
                    menuPosition={"absolute"}
                    menuPlacement={"bottom"}
                    styles={{
                        menuPortal: (base) => ({
                            ...base,
                            zIndex: 9999,
                            color: "#000000",
                        }),
                    }}
                    menuPortalTarget={document.body}
                    value={this.getSelectedEventType()}
                />

                {EventType.is(
                    EventType.RAID_EVENT,
                    this.getSelectedEventTypeName()
                ) ? (
                    <div className="my-4">
                        <Select
                            onChange={this.setRaidEvent.bind(this)}
                            options={this.optionsForRaids()}
                            menuPosition={"absolute"}
                            menuPlacement={"bottom"}
                            styles={{
                                menuPortal: (base) => ({
                                    ...base,
                                    zIndex: 9999,
                                    color: "#000000",
                                }),
                            }}
                            menuPortalTarget={document.body}
                            value={this.getSelectedRaid()}
                        />
                    </div>
                ) : null}

                <div className="grid md:grid-cols-2 gap-2">
                    <div className="my-4">
                        <div className="my-3 dark:text-gray-300">
                            <strong>Start Date (and time)</strong>
                        </div>
                        <DatePicker
                            selected={this.state.selected_start_date}
                            onChange={(date) =>
                                date !== null ? this.setStartDate(date) : null
                            }
                            showTimeSelect
                            filterTime={this.filterPassedTime.bind(this)}
                            dateFormat="MMMM d, yyyy h:mm aa"
                            className={
                                "border-2 border-gray-300 rounded-md p-2"
                            }
                            withPortal
                        />
                    </div>

                    <div className="my-4">
                        <div className="my-3 dark:text-gray-300">
                            <strong>End Date (and time)</strong>
                        </div>
                        <DatePicker
                            selected={this.state.selected_end_date}
                            onChange={(date) =>
                                date !== null ? this.setEndDate(date) : null
                            }
                            showTimeSelect
                            filterTime={this.filterPassedTime.bind(this)}
                            filterDate={this.filterEndDates.bind(this)}
                            dateFormat="MMMM d, yyyy h:mm aa"
                            className={
                                "border-2 border-gray-300 rounded-md p-2"
                            }
                            withPortal
                        />
                    </div>
                </div>

                <div className="mt-4 mb-8">
                    <div className="my-3 dark:text-gray-300">
                        <strong>Description</strong>
                    </div>
                    <textarea
                        rows={5}
                        cols={45}
                        onChange={this.setDescription.bind(this)}
                        className="border-2 border-gray-300 p-4"
                        value={this.state.event_description}
                    />
                </div>
            </Fragment>
        );
    }
}
