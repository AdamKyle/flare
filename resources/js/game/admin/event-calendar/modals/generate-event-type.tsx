import React, { Fragment } from "react";
import GenerateExtentTypeProps from "../types/modals/generate-event-type-props";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import GenerateExtentTypeState from "../types/modals/generate-event-type-state";
import { setHours, setMinutes } from "date-fns";
import DatePicker from "react-datepicker";
import Select from "react-select";
import Dialogue from "../../../components/ui/dialogue/dialogue";

export default class GenerateEventType extends React.Component<
    GenerateExtentTypeProps,
    GenerateExtentTypeState
> {
    private type_options: { label: string; value: string }[] | [];

    constructor(props: GenerateExtentTypeProps) {
        super(props);

        this.state = {
            action_in_progress: false,
            form_data: {
                selected_event_type: null,
                event_generation_times: null,
                selected_start_date: setHours(setMinutes(new Date(), 0), 9),
            },
            error_message: null,
        };

        this.type_options = [
            {
                label: "Weekly Celestial Spawns",
                value: "0",
            },
            {
                label: "Monthly PVP",
                value: "1",
            },
            {
                label: "Weekly Currency Drops",
                value: "2",
            },
        ];
    }

    handleGenerate() {
        this.setState(
            {
                error_message: null,
            },
            () => {
                if (!this.isDataValid()) {
                    this.setState({
                        error_message:
                            "No field can be left blank. Please make sure the form is filled out.",
                    });

                    return;
                }

                console.log(this.state.form_data);
            }
        );
    }

    isDataValid(): boolean {
        const formData = this.state.form_data;

        return Object.values(formData).every(value => value !== null);
    }

    setTypeOfEvent(data: any): void {
        this.setState({
            form_data: {
                ...this.state.form_data,
                ...{ selected_event_type: parseInt(data.value) },
            },
        });
    }

    setStartDate(date: Date) {
        this.setState({
            form_data: {
                ...this.state.form_data,
                ...{ selected_start_date: date },
            },
        });
    }

    setEventGenerationTimes(event: React.ChangeEvent<HTMLInputElement>) {
        this.setState({
            form_data: {
                ...this.state.form_data,
                ...{ event_generation_times: parseInt(event.target.value) },
            },
        });
    }

    selectedEventTypeValue(): { label: string; value: string }[] | [] {
        const foundType = this.type_options.filter(
            (type: { label: string; value: string }) => {
                return parseInt(type.value) === this.state.form_data.selected_event_type;
            }
        );

        if (foundType.length > 0) {
            return foundType;
        }

        return [{ label: "Please select event type", value: "" }];
    }

    render() {
        return (
            <Dialogue
                is_open={this.props.is_open}
                handle_close={this.props.handle_close}
                title={"Generate Event Type"}
                primary_button_disabled={this.state.action_in_progress}
                secondary_actions={{
                    secondary_button_disabled: this.state.action_in_progress,
                    secondary_button_label: "Generate",
                    handle_action: this.handleGenerate.bind(this),
                }}
            >
                <Fragment>
                    {this.state.error_message !== null ? (
                        <DangerAlert additional_css="my-4">
                            {this.state.error_message}
                        </DangerAlert>
                    ) : null}
                    <p className="my-4">
                        Here you can generate an event of a specif type that
                        should take place weekly or monthly and generate a
                        specific amount of events into the future based on the
                        date and time you provide.
                    </p>
                    <p className="my-4">
                        Events generated this way will generate for ever based
                        on the amount you want generated from the date selected
                        and based on how far out.
                    </p>

                    <div className="my-4">
                        <div className="my-3 dark:text-gray-300">
                            <strong>Start Date (and time)</strong>
                        </div>
                        <DatePicker
                            selected={this.state.form_data.selected_start_date}
                            onChange={(date) =>
                                date !== null ? this.setStartDate(date) : null
                            }
                            showTimeSelect
                            dateFormat="MMMM d, yyyy h:mm aa"
                            className={
                                "border-2 border-gray-300 rounded-md p-2"
                            }
                            withPortal
                        />
                    </div>
                    <Select
                        onChange={this.setTypeOfEvent.bind(this)}
                        options={this.type_options}
                        menuPosition={"absolute"}
                        menuPlacement={"bottom"}
                        styles={{
                            menuPortal: (base: any) => ({
                                ...base,
                                zIndex: 9999,
                                color: "#000000",
                            }),
                        }}
                        menuPortalTarget={document.body}
                        value={this.selectedEventTypeValue()}
                    />
                    <div className="my-5">
                        <div className="my-3 dark:text-gray-300">
                            <strong>
                                How Far into the future should we generate? (Max
                                is 15 Weeks/months)
                            </strong>
                        </div>
                        <input
                            type="number"
                            className="form-control"
                            min={1}
                            max={15}
                            onChange={this.setEventGenerationTimes.bind(this)}
                        />
                    </div>
                    {this.state.action_in_progress ? (
                        <LoadingProgressBar />
                    ) : null}
                </Fragment>
            </Dialogue>
        );
    }
}
