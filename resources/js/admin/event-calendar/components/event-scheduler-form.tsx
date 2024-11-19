import React, { Fragment, ReactNode } from "react";
import DateTimePicker from "react-datetime-picker";
import Select from "react-select";
import { setHours, setMinutes } from "date-fns";
import EventSchedulerFormState from "../types/components/event-scheduler-form-state";
import EventSchedulerFormProps from "../types/components/event-scheduler-form-props";
import EventType from "../values/EventType";
import { Value } from "react-datetime-picker/src/shared/types";

// Styles for the date picker:
import "react-datetime-picker/dist/DateTimePicker.css";
import "react-calendar/dist/Calendar.css";
import "react-clock/dist/Clock.css";
import InfoAlert from "../../../game/components/ui/alerts/simple-alerts/info-alert";
import clsx from "clsx";

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
            raids_for_event: [],
            error_message: null,
        };
    }

    componentDidMount() {
        this.setState(
            {
                selected_start_date: new Date(this.props.start_date),
                selected_end_date: new Date(this.props.start_date),
            },
            () => {
                this.props.update_parent(this.state);
            },
        );

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
                if (
                    EventType.isEventOfYearlyTypes(
                        this.getSelectedEventTypeName(),
                    )
                ) {
                    const newDate = new Date(this.props.start_date);

                    newDate.setMonth(newDate.getMonth() + 3);

                    this.setEndDate(newDate);
                }

                this.props.update_parent(this.state);
            },
        );
    }

    setDescription(event: React.ChangeEvent<HTMLTextAreaElement>) {
        this.setState(
            {
                event_description: event.target.value,
            },
            () => {
                this.props.update_parent(this.state);
            },
        );
    }

    setStartDate(value: Value) {
        this.setState(
            {
                selected_start_date: value as Date,
                error_message: null,
            },
            () => {
                if (
                    EventType.isEventOfYearlyTypes(
                        this.getSelectedEventTypeName(),
                    )
                ) {
                    const newDate = new Date(value as Date);

                    newDate.setMonth(newDate.getMonth() + 3);

                    this.setEndDate(newDate);

                    if (this.state.raids_for_event.length < 2) {
                        this.setState({
                            error_message:
                                "You need to select two raids for this type of event. If you do not have two RaidSection, go make another one.",
                        });

                        this.props.update_parent(this.state);

                        return;
                    }

                    this.updateYearlyRaidStartAndEndDates(value as Date);
                }

                this.props.update_parent(this.state);
            },
        );
    }

    updateYearlyRaidStartAndEndDates(startDate: Date) {
        const updatedRaids = [...this.state.raids_for_event];

        updatedRaids.forEach((raid, index) => {
            let start, end;

            if (index === 0) {
                start = new Date(startDate);
                start.setDate(start.getDate() + 14);
                end = new Date(start);
                end.setMonth(end.getMonth() + 1);
            } else if (index === 1) {
                start = new Date(updatedRaids[index - 1].end_date!);
                start.setDate(start.getDate() + 7);
                end = new Date(start);
                end.setMonth(end.getMonth() + 1);
            }

            raid.start_date = start;
            raid.end_date = end;
        });

        this.setState(
            {
                raids_for_event: updatedRaids,
            },
            () => {
                this.props.update_parent(this.state);
            },
        );
    }

    setEndDate(value: Value) {
        this.setState(
            {
                selected_end_date: value as Date,
            },
            () => {
                this.props.update_parent(this.state);
            },
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
            },
        );
    }

    optionsForEventType() {
        const types = this.props.event_types.map(
            (eventType: string, index: number) => {
                return {
                    label: eventType,
                    value: index,
                };
            },
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
            },
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
            (raid: any) => raid.id === this.state.selected_raid,
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
            },
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

    setYearlyRaidSelectedData(data: any, index: number) {
        const updatedRaids = [...this.state.raids_for_event];

        if (updatedRaids[index] === undefined) {
            updatedRaids[index] = { selected_raid: 0 };
        }

        updatedRaids[index] = {
            selected_raid: data.value,
        };

        this.setState(
            {
                raids_for_event: updatedRaids.filter((raidForEvent) => {
                    return (
                        raidForEvent !== null || raidForEvent !== "undefined"
                    );
                }),
            },
            () => {
                this.updateYearlyRaidStartAndEndDates(
                    this.state.selected_start_date as Date,
                );

                this.props.update_parent(this.state);
            },
        );
    }

    getSelectedYearlyRaidEvent(index: number) {
        const raidsForYearlyEvent = this.state.raids_for_event;

        if (raidsForYearlyEvent[index]) {
            const foundRaid = this.props.raids.find(
                (raid: any) =>
                    raid.id === raidsForYearlyEvent[index].selected_raid,
            );

            if (foundRaid) {
                return [
                    {
                        label: foundRaid.name,
                        value: foundRaid.id,
                    },
                ];
            }
        }

        return [
            {
                label: "Please select a raid",
                value: 0,
            },
        ];
    }

    renderYearlyRaidSelectionElements(): ReactNode {
        const elements: ReactNode[] = [];

        for (var i = 0; i <= 1; i++) {
            elements.push(
                <div
                    className={clsx("mt-4", {
                        "mb-4": i === 1,
                    })}
                >
                    <Select
                        onChange={(selectedOption: any) =>
                            this.setYearlyRaidSelectedData(selectedOption, i)
                        }
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
                        value={this.getSelectedYearlyRaidEvent(i)}
                        key={i + "-raid-selection-for-yearly-event"}
                    />
                </div>,
            );
        }

        return elements;
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

                {EventType.isEventOfYearlyTypes(
                    this.getSelectedEventTypeName(),
                ) ? (
                    <div className="my-4">
                        <h4 className="mb-4">Yealry Event Raids</h4>
                        <p className="my-2">
                            All yearly events will last three months.
                        </p>
                        <p>
                            There are two raids for every yearly event. The
                            first raid will start automatically after 4 weeks of
                            the event, while the second raid will start 2 weeks
                            after the first raid ends. All raids will last 1
                            month in length.
                        </p>
                        {this.renderYearlyRaidSelectionElements()}
                    </div>
                ) : null}

                {EventType.is(
                    EventType.RAID_EVENT,
                    this.getSelectedEventTypeName(),
                ) &&
                !EventType.isEventOfYearlyTypes(
                    this.getSelectedEventTypeName(),
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

                <div className="my-4">
                    <div className="my-3 dark:text-gray-300">
                        <strong>Start Date (and time)</strong>
                    </div>
                    <DateTimePicker
                        onChange={this.setStartDate.bind(this)}
                        value={this.state.selected_start_date}
                        className="w-full"
                    />
                </div>

                <div className="my-4">
                    <div className="my-3 dark:text-gray-300">
                        <strong>End Date (and time)</strong>
                    </div>
                    {EventType.isEventOfYearlyTypes(
                        this.getSelectedEventTypeName(),
                    ) ? (
                        <InfoAlert additional_css="my-4">
                            A yearly event is selected, the start date will add
                            three months to it, and end on that date at the same
                            time.
                        </InfoAlert>
                    ) : null}
                    <DateTimePicker
                        onChange={this.setEndDate.bind(this)}
                        value={this.state.selected_end_date}
                        className="w-full"
                        disabled={EventType.isEventOfYearlyTypes(
                            this.getSelectedEventTypeName(),
                        )}
                    />
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
