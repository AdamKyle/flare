import React, { Component, CSSProperties, ReactNode } from "react";
import GridOverlayProps from "./types/grid-overlay-props";
import GridOverlayState from "./types/grid-overlay-state";
import LocationDetails from "../../game/sections/map/types/location-details";
import LocationPin from "../../game/sections/components/locations/location-pin";

export default class GridOverlay extends Component<
    GridOverlayProps,
    GridOverlayState
> {
    constructor(props: GridOverlayProps) {
        super(props);
        this.state = {
            coordinates: { x: 0, y: 0 },
            showTooltip: false,
            tooltipPosition: "top", // Default tooltip position
            snapped: false, // Track if the coordinates are snapped
        };
    }

    handleMouseMove = (e: React.MouseEvent<HTMLDivElement, MouseEvent>) => {
        if (this.state.snapped) {
            return; // If coordinates are snapped, do not update on mouse move
        }

        const { clientX, clientY } = e;
        const { left, top, width, height } =
            e.currentTarget.getBoundingClientRect();
        const offsetX = Math.floor(clientX - left);
        const offsetY = Math.floor(clientY - top);
        const isTop = offsetY < height / 2;
        const isLeft = offsetX < width / 2;

        let tooltipPosition = "";

        if (isTop) {
            tooltipPosition += "top";
        } else {
            tooltipPosition += "bottom";
        }

        if (isLeft) {
            tooltipPosition += "-left";
        } else {
            tooltipPosition += "-right";
        }

        this.setState({
            coordinates: { x: offsetX, y: offsetY },
            showTooltip: true,
            tooltipPosition,
        });
    };

    handleMouseLeave = () => {
        this.setState({
            showTooltip: false,
            snapped: false, // Reset snapped state on mouse leave
        });
    };

    handleLocationMouseEnter = (x: number, y: number) => {
        this.setState({
            coordinates: { x, y },
            snapped: true,
        });
    };

    renderGrid() {
        const { coordinates } = this.props;
        const { x: xCoords, y: yCoords } = coordinates;

        const gridCells = [];

        // Loop through the y coordinates first to fill in rows
        for (let yIndex = 0; yIndex < yCoords.length; yIndex++) {
            const yPos = yCoords[yIndex];

            // Loop through the x coordinates to fill in columns within each row
            for (let xIndex = 0; xIndex < xCoords.length; xIndex++) {
                const xPos = xCoords[xIndex];

                // Create a grid cell with the current x and y position, adjusted by 8 pixels
                gridCells.push(
                    <div
                        key={`${xPos}-${yPos}`} // Unique key for each grid cell
                        className="grid-cell"
                        style={{
                            left: xPos - 8,
                            top: yPos,
                            width: "16px",
                            height: "16px",
                        }}
                    ></div>,
                );
            }
        }

        return <div className="grid-overlay">{gridCells}</div>;
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
                            this.handleLocationMouseEnter(
                                location.x,
                                location.y,
                            )
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
                        this.handleLocationMouseEnter(location.x, location.y)
                    }
                />
            );
        });
    }

    render() {
        const { mapSrc } = this.props;
        const { coordinates, showTooltip, tooltipPosition, snapped } =
            this.state;

        if (!mapSrc) {
            return <div>Image source is not provided.</div>;
        }

        let tooltipStyle: CSSProperties = {
            position: "absolute",
            background: "rgba(0, 0, 0, 0.5)",
            color: "white",
            padding: "5px",
            borderRadius: "3px",
            pointerEvents: "none",
            zIndex: 999,
            visibility: showTooltip ? "visible" : "hidden",
        };

        const tooltipOffsetX = 10; // Offset value to keep the tooltip close to the mouse cursor
        const tooltipOffsetY = 10;

        // Adjust tooltip position based on the calculated tooltipPosition state and the offset
        switch (tooltipPosition) {
            case "top-left":
                tooltipStyle = {
                    ...tooltipStyle,
                    left: coordinates.x + tooltipOffsetX,
                    top: coordinates.y + tooltipOffsetY,
                };
                break;
            case "top-right":
                tooltipStyle = {
                    ...tooltipStyle,
                    left: coordinates.x - 70,
                    top: coordinates.y + tooltipOffsetY,
                };
                break;
            case "bottom-left":
                tooltipStyle = {
                    ...tooltipStyle,
                    left: coordinates.x + tooltipOffsetX,
                    top: coordinates.y - tooltipOffsetY,
                };
                break;
            case "bottom-right":
                tooltipStyle = {
                    ...tooltipStyle,
                    left: coordinates.x - 70,
                    top: coordinates.y - tooltipOffsetY,
                };
                break;
            case "bottom":
                tooltipStyle = {
                    ...tooltipStyle,
                    left: coordinates.x,
                    top: coordinates.y - tooltipOffsetY,
                };
                break;
            case "left":
            case "left-top":
            case "left-bottom":
                tooltipStyle = {
                    ...tooltipStyle,
                    left: coordinates.x + tooltipOffsetX,
                    top: coordinates.y,
                };
                break;
            case "right":
            case "right-top":
            case "right-bottom":
                tooltipStyle = {
                    ...tooltipStyle,
                    left: coordinates.x - 40,
                    top: coordinates.y,
                };
                break;
            default:
                tooltipStyle = {
                    ...tooltipStyle,
                    left: coordinates.x + tooltipOffsetX,
                    top: coordinates.y,
                };
        }

        return (
            <div
                className="image-container game-map"
                onMouseMove={this.handleMouseMove}
                onMouseLeave={this.handleMouseLeave}
                style={{ position: "relative" }}
            >
                <img
                    src={mapSrc}
                    alt="Background"
                    className="background-image"
                />
                {this.renderGrid()}
                {this.renderLocationPins()}
                <div style={tooltipStyle}>
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
                            top: coordinates.y - 16,
                            pointerEvents: "none",
                        }}
                    ></div>
                )}
            </div>
        );
    }
}
