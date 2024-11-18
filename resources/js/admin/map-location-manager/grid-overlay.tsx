import React, { Component, CSSProperties, ReactNode } from "react";
import LocationPin from "../../game/sections/components/locations/location-pin";
import LocationDetails from "../../game/sections/map/types/location-details";
import { gridOverLayContainer } from "./container/grid-overlay-container";
import MouseHandlers from "./grid/mouse-handlers";
import ToolTipHandler from "./grid/tool-tip-handler";
import MoveLocationDialogue from "./modals/move-location-dialogue";
import GridOverlayProps from "./types/grid-overlay-props";
import GridOverlayState from "./types/grid-overlay-state";
import NpcDetails from "./types/deffinitions/npc-details";

export default class GridOverlay extends Component<
    GridOverlayProps,
    GridOverlayState
> {
    private mouseHandlers: MouseHandlers;

    private toolTipHandler: ToolTipHandler;

    private gridContainer: React.RefObject<HTMLDivElement>;

    constructor(props: GridOverlayProps) {
        super(props);

        this.state = {
            coordinates: { x: 0, y: 0 },
            showTooltip: false,
            tooltipPosition: "top",
            snapped: false,
            hoveredGridCell: { x: null, y: null },
            showModal: false,
        };

        this.mouseHandlers = gridOverLayContainer().fetch(MouseHandlers);
        this.toolTipHandler = gridOverLayContainer().fetch(ToolTipHandler);

        this.mouseHandlers = this.mouseHandlers.initialize(this);

        this.gridContainer = React.createRef();
    }

    renderGrid() {
        const { coordinates } = this.props;
        const { x: xCoords, y: yCoords } = coordinates;
        const { hoveredGridCell, snapped } = this.state;

        const gridCells = [];

        // Loop through the y coordinates first to fill in rows
        for (let yIndex = 0; yIndex < yCoords.length; yIndex++) {
            const yPos = yCoords[yIndex];

            // Loop through the x coordinates to fill in columns within each row
            for (let xIndex = 0; xIndex < xCoords.length; xIndex++) {
                const xPos = xCoords[xIndex] - 8;
                const isHovered =
                    hoveredGridCell.x === xPos && hoveredGridCell.y === yPos;

                gridCells.push(
                    <button
                        key={`${xPos}-${yPos}`} // Unique key for each grid cell
                        className={`grid-cell ${isHovered ? "hovered" : ""}`} // Tailwind CSS class for grid cell
                        style={{
                            left: `${xPos}px`,
                            top: `${yPos}px`,
                            width: "16px",
                            height: "16px",
                            position: "absolute", // Make sure grid cells are positioned absolutely
                            cursor: "pointer",
                        }}
                        onMouseEnter={() =>
                            this.mouseHandlers.handleGridCellMouseEnter(
                                xPos,
                                yPos,
                            )
                        }
                        onMouseLeave={this.mouseHandlers.handleMouseLeave}
                    ></button>,
                );
            }
        }

        return <div className="grid-overlay">{gridCells}</div>;
    }

    renderNpcs(): ReactNode {
        return this.props.npcs.map((npc: NpcDetails) => {
            return (
                <button
                    key={npc.id}
                    data-npc-id={npc.id}
                    className={"map-x-pin"}
                    style={{
                        top: npc.y_position,
                        left: npc.x_position,
                    }}
                    onMouseEnter={() => {
                        this.mouseHandlers.handleLocationMouseEnter(
                            npc.x_position,
                            npc.y_position,
                        );
                    }}
                    onMouseLeave={this.mouseHandlers.handleLocationMouseLeave}
                ></button>
            );
        });
    }

    renderLocationPins(): ReactNode {
        return this.props.locations.map((location: LocationDetails) => {
            if (location.is_port) {
                return (
                    <LocationPin
                        key={location.id}
                        location={{
                            id: location.id,
                            x: location.x,
                            y: location.y,
                        }}
                        openLocationDetails={() => {}}
                        pin_class={"port-x-pin"}
                        onMouseEnter={() =>
                            this.mouseHandlers.handleLocationMouseEnter(
                                location.x,
                                location.y,
                            )
                        }
                        onMouseLeave={
                            this.mouseHandlers.handleLocationMouseLeave
                        }
                    />
                );
            }

            return (
                <LocationPin
                    key={location.id}
                    location={{ id: location.id, x: location.x, y: location.y }}
                    openLocationDetails={() => {}}
                    pin_class={"location-x-pin"}
                    onMouseEnter={() =>
                        this.mouseHandlers.handleLocationMouseEnter(
                            location.x,
                            location.y,
                        )
                    }
                    onMouseLeave={this.mouseHandlers.handleLocationMouseLeave}
                />
            );
        });
    }

    manageModal() {
        this.setState({
            showModal: !this.state.showModal,
        });
    }

    render() {
        const { mapSrc } = this.props;
        const { coordinates, showTooltip, tooltipPosition, snapped } =
            this.state;

        if (!mapSrc) {
            return <div>Image source is not provided.</div>;
        }

        const toolTipStyle: CSSProperties = this.toolTipHandler.getOffSet(
            tooltipPosition,
            coordinates,
            showTooltip,
        );

        return (
            <div
                className="image-container game-map"
                onMouseMove={this.mouseHandlers.handleMouseMove}
                onMouseLeave={this.mouseHandlers.handleMouseLeave}
                style={{
                    position: "relative",
                    width: "2500px",
                    height: "2500px",
                }}
                ref={this.gridContainer}
            >
                <img
                    src={mapSrc}
                    alt="Background"
                    className="background-image"
                    style={{ width: "100%", height: "100%" }}
                />
                {this.renderGrid()}
                {this.renderLocationPins()}
                {this.renderNpcs()}
                <div style={toolTipStyle}>
                    Coordinates: ({Math.floor(coordinates.x)},{" "}
                    {Math.floor(coordinates.y)})
                </div>
                {snapped && (
                    <div
                        style={{
                            position: "absolute",
                            width: "16px",
                            height: "16px",
                            backgroundColor: "rgba(255, 0, 0, 0.5)",
                            left: coordinates.x - 8,
                            top: coordinates.y,
                            cursor: "pointer",
                        }}
                        onClick={this.manageModal.bind(this)}
                    ></div>
                )}
                {this.state.showModal ? (
                    <MoveLocationDialogue
                        is_open={this.state.showModal}
                        closeModal={this.manageModal.bind(this)}
                        coordinates={coordinates}
                        locations={this.props.locations}
                        npcs={this.props.npcs}
                        updateLocationsAndNpcs={
                            this.props.updateLocationsAndNpcs
                        }
                        map_id={this.props.map_id}
                    />
                ) : null}
            </div>
        );
    }
}
