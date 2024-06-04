import React from "react";
import Dialogue from "../../../game/components/ui/dialogue/dialogue";
import Select from "react-select";
import LocationDetails from "../../../game/sections/map/types/location-details";
import PrimaryButton from "../../../game/components/ui/buttons/primary-button";

interface MoveLocationDialogueProps {
    is_open: boolean;
    closeModal: () => void;
    coordinates: { x: number; y: number };
    locations: LocationDetails[] | [];
}

interface MoveLocationDialogueState {
    selected_location_id: number;
}

export default class MoveLocationDialogue extends React.Component<
    MoveLocationDialogueProps,
    MoveLocationDialogueState
> {
    constructor(props: MoveLocationDialogueProps) {
        super(props);

        this.state = {
            selected_location_id: 0,
        };
    }

    setSelectedLocation(data: any) {
        if (data.value === 0) {
            return;
        }

        this.setState({
            selected_location_id: parseInt(data.value) || 0,
        });
    }

    options() {
        return this.props.locations.map((location: LocationDetails) => {
            return {
                label: location.name + (location.is_port ? " (Port)" : ""),
                value: location.id,
            };
        });
    }

    getDefaultOption() {
        const filteredLocation = this.props.locations.filter(
            (location: LocationDetails) => {
                return location.id === this.state.selected_location_id;
            },
        );

        if (filteredLocation.length > 0) {
            return [
                {
                    label:
                        filteredLocation[0].name +
                        (filteredLocation[0].is_port ? " (Port)" : ""),
                    value: filteredLocation[0].id,
                },
            ];
        }

        return [
            {
                label: "Please select",
                value: 0,
            },
        ];
    }

    render() {
        return (
            <Dialogue
                is_open={this.props.is_open}
                title={"Location Mover"}
                handle_close={this.props.closeModal}
            >
                <p>
                    Select a location from below that you want to be moved here
                    (X/Y): {this.props.coordinates.x} /{" "}
                    {this.props.coordinates.y}
                </p>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                <Select
                    onChange={this.setSelectedLocation.bind(this)}
                    options={this.options()}
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
                    value={this.getDefaultOption()}
                />
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                <PrimaryButton
                    button_label={"Move Location"}
                    on_click={() => {}}
                    disabled={this.state.selected_location_id === 0}
                />
            </Dialogue>
        );
    }
}
