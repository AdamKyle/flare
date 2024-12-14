import React from "react";
import Select from "react-select";
import LocationDetails from "../../../game/sections/map/types/location-details";
import MoveLocationAjax from "../ajax/move-location-ajax";
import { gridOverLayContainer } from "../container/grid-overlay-container";
import NpcDetails from "../types/deffinitions/npc-details";
import LoadingProgressBar from "../../components/ui/progress-bars/loading-progress-bar";
import PrimaryButton from "../../components/ui/buttons/primary-button";
import Dialogue from "../../components/ui/dialogue/dialogue";

interface MoveLocationDialogueProps {
    is_open: boolean;
    closeModal: () => void;
    coordinates: { x: number; y: number };
    locations: LocationDetails[] | [];
    npcs: NpcDetails[] | [];
    updateLocationsAndNpcs: (
        locations: LocationDetails[] | [],
        npcs: NpcDetails[] | [],
    ) => void;
    map_id: number;
}

interface MoveLocationDialogueState {
    selected_location_id: number;
    selected_npc_id: number;
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
            selected_npc_id: 0,
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

    setSelectedNpc(data: any) {
        if (data.value === 0) {
            return;
        }

        this.setState({
            selected_npc_id: parseInt(data.value) || 0,
        });
    }

    locationOptions() {
        return this.props.locations.map((location: LocationDetails) => {
            return {
                label: location.name + (location.is_port ? " (Port)" : ""),
                value: location.id,
            };
        });
    }

    npcOptions() {
        return this.props.npcs.map((npc: NpcDetails) => {
            return {
                label: npc.real_name,
                value: npc.id,
            };
        });
    }

    getDefaultLocationOption() {
        const filteredLocation = this.props.locations.filter(
            (location: LocationDetails) => {
                return (
                    location.id === this.state.selected_location_id ||
                    (location.x === this.props.coordinates.x &&
                        location.y === this.props.coordinates.y)
                );
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
                label: "Please select location",
                value: 0,
            },
        ];
    }

    getDefaultNpcOption() {
        const filteredNpc = this.props.npcs.filter((npc: NpcDetails) => {
            return (
                npc.id === this.state.selected_npc_id ||
                (npc.x_position === this.props.coordinates.x &&
                    npc.y_position === this.props.coordinates.y)
            );
        });

        if (filteredNpc.length > 0) {
            return [
                {
                    label: filteredNpc[0].name,
                    value: filteredNpc[0].id,
                },
            ];
        }

        return [
            {
                label: "Please select npc",
                value: 0,
            },
        ];
    }

    moveLocationOrNopc() {
        this.setState(
            {
                processing: true,
            },
            () => {
                this.moveLocationAjax.moveLocation(
                    this,
                    this.props.map_id,
                    this.state.selected_location_id,
                    this.state.selected_npc_id,
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
                    options={this.locationOptions()}
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
                    value={this.getDefaultLocationOption()}
                />

                <div className="my-4">
                    <Select
                        onChange={this.setSelectedNpc.bind(this)}
                        options={this.npcOptions()}
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
                        value={this.getDefaultNpcOption()}
                    />
                </div>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>

                {this.state.processing ? <LoadingProgressBar /> : null}

                <PrimaryButton
                    button_label={"Move Location/NPC"}
                    on_click={this.moveLocationOrNopc.bind(this)}
                    disabled={
                        this.state.processing ||
                        (this.state.selected_location_id === 0 &&
                            this.state.selected_npc_id === 0)
                    }
                />
            </Dialogue>
        );
    }
}
