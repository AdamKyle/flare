var __extends =
    (this && this.__extends) ||
    (function () {
        var extendStatics = function (d, b) {
            extendStatics =
                Object.setPrototypeOf ||
                ({ __proto__: [] } instanceof Array &&
                    function (d, b) {
                        d.__proto__ = b;
                    }) ||
                function (d, b) {
                    for (var p in b)
                        if (Object.prototype.hasOwnProperty.call(b, p))
                            d[p] = b[p];
                };
            return extendStatics(d, b);
        };
        return function (d, b) {
            if (typeof b !== "function" && b !== null)
                throw new TypeError(
                    "Class extends value " +
                        String(b) +
                        " is not a constructor or null",
                );
            extendStatics(d, b);
            function __() {
                this.constructor = d;
            }
            d.prototype =
                b === null
                    ? Object.create(b)
                    : ((__.prototype = b.prototype), new __());
        };
    })();
import React, { Component } from "react";
import LocationPin from "../../game/sections/components/locations/location-pin";
import MouseHandlers from "./grid/mouse-handlers";
import { gridOverLayContainer } from "./container/grid-overlay-container";
import ToolTipHandler from "./grid/tool-tip-handler";
import MoveLocationDialogue from "./modals/move-location-dialogue";
var GridOverlay = (function (_super) {
    __extends(GridOverlay, _super);
    function GridOverlay(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            coordinates: { x: 0, y: 0 },
            showTooltip: false,
            tooltipPosition: "top",
            snapped: false,
            hoveredGridCell: { x: null, y: null },
            showModal: false,
        };
        _this.mouseHandlers = gridOverLayContainer().fetch(MouseHandlers);
        _this.toolTipHandler = gridOverLayContainer().fetch(ToolTipHandler);
        _this.mouseHandlers = _this.mouseHandlers.initialize(_this);
        _this.gridContainer = React.createRef();
        return _this;
    }
    GridOverlay.prototype.renderGrid = function () {
        var _this = this;
        var coordinates = this.props.coordinates;
        var xCoords = coordinates.x,
            yCoords = coordinates.y;
        var _a = this.state,
            hoveredGridCell = _a.hoveredGridCell,
            snapped = _a.snapped;
        var gridCells = [];
        var _loop_1 = function (yIndex) {
            var yPos = yCoords[yIndex];
            var _loop_2 = function (xIndex) {
                var xPos = xCoords[xIndex];
                var isHovered =
                    hoveredGridCell.x === xPos && hoveredGridCell.y === yPos;
                gridCells.push(
                    React.createElement("button", {
                        key: "".concat(xPos, "-").concat(yPos),
                        className: "grid-cell",
                        style: {
                            left: xPos - 8,
                            top: yPos,
                            width: "16px",
                            height: "16px",
                            position: "absolute",
                            cursor: "pointer",
                        },
                        onMouseEnter: function () {
                            return _this.mouseHandlers.handleGridCellMouseEnter(
                                xPos,
                                yPos,
                            );
                        },
                        onMouseLeave: this_1.mouseHandlers.handleMouseLeave,
                    }),
                );
            };
            for (var xIndex = 0; xIndex < xCoords.length; xIndex++) {
                _loop_2(xIndex);
            }
        };
        var this_1 = this;
        for (var yIndex = 0; yIndex < yCoords.length; yIndex++) {
            _loop_1(yIndex);
        }
        return React.createElement(
            "div",
            { className: "grid-overlay" },
            gridCells,
        );
    };
    GridOverlay.prototype.renderLocationPins = function () {
        var _this = this;
        return this.props.locations.map(function (location) {
            if (location.is_port) {
                return React.createElement(LocationPin, {
                    key: location.id,
                    location: {
                        id: location.id,
                        x: location.x,
                        y: location.y,
                    },
                    openLocationDetails: function () {},
                    pin_class: "port-x-pin",
                    onMouseEnter: function () {
                        return _this.mouseHandlers.handleLocationMouseEnter(
                            location.x,
                            location.y,
                        );
                    },
                    onMouseLeave: _this.mouseHandlers.handleLocationMouseLeave,
                });
            }
            return React.createElement(LocationPin, {
                key: location.id,
                location: { id: location.id, x: location.x, y: location.y },
                openLocationDetails: function () {},
                pin_class: "location-x-pin",
                onMouseEnter: function () {
                    return _this.mouseHandlers.handleLocationMouseEnter(
                        location.x,
                        location.y,
                    );
                },
                onMouseLeave: _this.mouseHandlers.handleLocationMouseLeave,
            });
        });
    };
    GridOverlay.prototype.manageModal = function () {
        this.setState({
            showModal: !this.state.showModal,
        });
    };
    GridOverlay.prototype.render = function () {
        var mapSrc = this.props.mapSrc;
        var _a = this.state,
            coordinates = _a.coordinates,
            showTooltip = _a.showTooltip,
            tooltipPosition = _a.tooltipPosition,
            snapped = _a.snapped;
        if (!mapSrc) {
            return React.createElement(
                "div",
                null,
                "Image source is not provided.",
            );
        }
        var toolTipStyle = this.toolTipHandler.getOffSet(
            tooltipPosition,
            coordinates,
            showTooltip,
        );
        return React.createElement(
            "div",
            {
                className: "image-container game-map",
                onMouseMove: this.mouseHandlers.handleMouseMove,
                onMouseLeave: this.mouseHandlers.handleMouseLeave,
                style: { position: "relative" },
                ref: this.gridContainer,
            },
            React.createElement("img", {
                src: mapSrc,
                alt: "Background",
                className: "background-image",
            }),
            this.renderGrid(),
            this.renderLocationPins(),
            React.createElement(
                "div",
                { style: toolTipStyle },
                "Coordinates: (",
                Math.floor(coordinates.x),
                ",",
                " ",
                Math.floor(coordinates.y),
                ")",
            ),
            snapped &&
                React.createElement("div", {
                    style: {
                        position: "absolute",
                        width: "16px",
                        height: "16px",
                        backgroundColor: "rgba(255, 0, 0, 0.5)",
                        left: coordinates.x - 8,
                        top: coordinates.y,
                        cursor: "pointer",
                    },
                    onClick: this.manageModal.bind(this),
                }),
            this.state.showModal
                ? React.createElement(MoveLocationDialogue, {
                      is_open: this.state.showModal,
                      closeModal: this.manageModal.bind(this),
                      coordinates: coordinates,
                      locations: this.props.locations,
                  })
                : null,
        );
    };
    return GridOverlay;
})(Component);
export default GridOverlay;
//# sourceMappingURL=grid-overlay.js.map
