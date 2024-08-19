import { injectable } from "tsyringe";
import React, { Component } from "react";
import GridOverlayProps from "../types/grid-overlay-props";
import GridOverlayState from "../types/grid-overlay-state";

@injectable()
export default class MouseHandlers {
    private component?: Component<GridOverlayProps, GridOverlayState>;

    constructor() {
        this.handleMouseMove = this.handleMouseMove.bind(this);
        this.handleMouseLeave = this.handleMouseLeave.bind(this);
    }

    initialize(
        component: Component<GridOverlayProps, GridOverlayState>,
    ): MouseHandlers {
        this.component = component;
        return this;
    }

    handleMouseMove(e: React.MouseEvent<HTMLDivElement, MouseEvent>) {
        if (!this.component) {
            throw new Error(
                "Component is not registered. Call initialize first.",
            );
        }

        if (this.component.state.showModal) {
            return;
        }

        const { clientX, clientY } = e;
        const { left, top } = e.currentTarget.getBoundingClientRect();
        const mouseX = clientX - left;
        const mouseY = clientY - top;

        const { x: xCoords, y: yCoords } = this.component.props.coordinates;

        const closestX = (xCoords as number[]).reduce<number>(
            (prev, curr) =>
                Math.abs(curr - mouseX) < Math.abs(prev - mouseX) ? curr : prev,
            xCoords[0] as number,
        );

        const closestY = (yCoords as number[]).reduce<number>(
            (prev, curr) =>
                Math.abs(curr - mouseY) < Math.abs(prev - mouseY) ? curr : prev,
            yCoords[0] as number,
        );

        this.component.setState({
            coordinates: { x: closestX, y: closestY },
            showTooltip: true,
            tooltipPosition: this.getTooltipPosition(closestX, closestY),
            hoveredGridCell: { x: closestX, y: closestY },
            snapped: true,
        });
    }

    handleGridCellMouseEnter(x: number, y: number) {
        if (!this.component) {
            throw new Error(
                "Component is not registered. Call initialize first.",
            );
        }

        const { x: xCoords, y: yCoords } = this.component.props.coordinates;

        const closestX = (xCoords as number[]).reduce<number>(
            (prev, curr) =>
                Math.abs(curr - x) < Math.abs(prev - x) ? curr : prev,
            xCoords[0] as number,
        );

        const closestY = (yCoords as number[]).reduce<number>(
            (prev, curr) =>
                Math.abs(curr - y) < Math.abs(prev - y) ? curr : prev,
            yCoords[0] as number,
        );

        this.component.setState({
            coordinates: { x: closestX, y: closestY },
            showTooltip: true,
            tooltipPosition: this.getTooltipPosition(closestX, closestY),
            snapped: true,
        });
    }

    handleMouseLeave() {
        if (!this.component) {
            throw new Error(
                "Component is not registered. Call initialize first.",
            );
        }

        this.component.setState({
            showTooltip: false,
            snapped: false,
            hoveredGridCell: { x: null, y: null },
        });
    }

    handleLocationMouseEnter = (x: number, y: number) => {
        if (!this.component) {
            throw new Error(
                "Component is not registered. Call initialize first.",
            );
        }

        this.component.setState({
            coordinates: { x, y },
            snapped: true,
            hoveredGridCell: { x, y },
        });
    };

    handleLocationMouseLeave = () => {
        if (!this.component) {
            throw new Error(
                "Component is not registered. Call initialize first.",
            );
        }

        this.component.setState({
            snapped: false,
            hoveredGridCell: { x: null, y: null },
        });
    };

    private getTooltipPosition(x: number, y: number) {
        if (!this.component) {
            throw new Error(
                "Component is not registered. Call initialize first.",
            );
        }

        const { x: xCoords, y: yCoords } = this.component.props.coordinates;
        const width = xCoords.length > 0 ? xCoords[xCoords.length - 1] : 0;
        const height = yCoords.length > 0 ? yCoords[yCoords.length - 1] : 0;

        const isTop = y < height / 2;
        const isLeft = x < width / 2;

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

        return tooltipPosition;
    }
}
