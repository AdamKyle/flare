import React from "react";
import Select from "react-select";
import PrimaryButton from "../../../game/components/ui/buttons/primary-button";
import Dialogue from "../../../game/components/ui/dialogue/dialogue";
import LoadingProgressBar from "../../../game/components/ui/progress-bars/loading-progress-bar";
import LocationDetails from "../../../game/sections/map/types/location-details";
import MoveLocationAjax from "../ajax/move-location-ajax";
import { gridOverLayContainer } from "../container/grid-overlay-container";

interface MoveLocationDialogueProps {
    is_open: boolean;
    closeModal: () => void;
    coordinates: { x: number; y: number };
    locations: LocationDetails[] | [];
    updateLocations: (locations: LocationDetails[] | []) => void;
}

interface MoveLocationDialogueState {
    selected_location_id: number;
    error_message: string | null;
    processing: boolean;
}

export default class MoveLocationDialogue extends React.Component<
    MoveLocationDialogueProps,
    MoveLocationDialogueState
> {
    private moveLocationAjax: MoveLocationAjax;

    constructor(props: MoveLocationDialogueProps) {
        super(props);

        this.state = {
            selected_location_id: 0,
            error_message: null,
            processing: false,
        };

        this.moveLocationAjax = gridOverLayContainer().fetch(MoveLocationAjax);
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

    moveLocation() {
        this.setState(
            {
                processing: true,
            },
            () => {
                this.moveLocationAjax.moveLocation(
                    this,
                    this.state.selected_location_id,
                    this.props.coordinates,
                );
            },
        );
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

                {this.state.processing ? <LoadingProgressBar /> : null}

                <PrimaryButton
                    button_label={"Move Location"}
                    on_click={this.moveLocation.bind(this)}
                    disabled={
                        this.state.processing ||
                        this.state.selected_location_id === 0
                    }
                />
            </Dialogue>
        );
    }
}
