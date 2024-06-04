import { CSSProperties } from "react";

interface ToolTipParams {
    tooltipPosition: string;
    tooltipStyle: CSSProperties;
    coordinates: { x: number; y: number };
    tooltipOffsetX: number;
    tooltipOffsetY: number;
}

export default class ToolTipHandler {
    private tooltipStyle: CSSProperties = {
        position: "absolute",
        background: "rgba(0, 0, 0, 0.5)",
        color: "white",
        padding: "5px",
        borderRadius: "3px",
        pointerEvents: "none",
        zIndex: 999,
    };

    public getOffSet(
        position: string,
        coordinates: { x: number; y: number },
        showTooltip: boolean,
    ): CSSProperties {
        const styles: CSSProperties = {
            ...this.tooltipStyle,
            visibility: showTooltip ? "visible" : "hidden",
        };

        const params = {
            tooltipPosition: position,
            tooltipStyle: styles,
            coordinates: coordinates,
            tooltipOffsetX: 10,
            tooltipOffsetY: 10,
        };

        return this.buildToolTipOffSet(params);
    }

    private buildToolTipOffSet(toolTipParams: ToolTipParams): CSSProperties {
        switch (toolTipParams.tooltipPosition) {
            case "top-left":
                toolTipParams.tooltipStyle = {
                    ...toolTipParams.tooltipStyle,
                    left:
                        toolTipParams.coordinates.x +
                        toolTipParams.tooltipOffsetX,
                    top:
                        toolTipParams.coordinates.y +
                        toolTipParams.tooltipOffsetY,
                };
                break;
            case "top-right":
                toolTipParams.tooltipStyle = {
                    ...toolTipParams.tooltipStyle,
                    left: toolTipParams.coordinates.x - 70,
                    top:
                        toolTipParams.coordinates.y +
                        toolTipParams.tooltipOffsetY,
                };
                break;
            case "bottom-left":
                toolTipParams.tooltipStyle = {
                    ...toolTipParams.tooltipStyle,
                    left:
                        toolTipParams.coordinates.x +
                        toolTipParams.tooltipOffsetX,
                    top:
                        toolTipParams.coordinates.y -
                        toolTipParams.tooltipOffsetY,
                };
                break;
            case "bottom-right":
                toolTipParams.tooltipStyle = {
                    ...toolTipParams.tooltipStyle,
                    left: toolTipParams.coordinates.x - 70,
                    top:
                        toolTipParams.coordinates.y -
                        toolTipParams.tooltipOffsetY,
                };
                break;
            case "bottom":
                toolTipParams.tooltipStyle = {
                    ...toolTipParams.tooltipStyle,
                    left: toolTipParams.coordinates.x,
                    top:
                        toolTipParams.coordinates.y -
                        toolTipParams.tooltipOffsetY,
                };
                break;
            case "left":
            case "left-top":
            case "left-bottom":
                toolTipParams.tooltipStyle = {
                    ...toolTipParams.tooltipStyle,
                    left:
                        toolTipParams.coordinates.x +
                        toolTipParams.tooltipOffsetX,
                    top: toolTipParams.coordinates.y,
                };
                break;
            case "right":
            case "right-top":
            case "right-bottom":
                toolTipParams.tooltipStyle = {
                    ...toolTipParams.tooltipStyle,
                    left: toolTipParams.coordinates.x - 40,
                    top: toolTipParams.coordinates.y,
                };
                break;
            default:
                toolTipParams.tooltipStyle = {
                    ...toolTipParams.tooltipStyle,
                    left:
                        toolTipParams.coordinates.x +
                        toolTipParams.tooltipOffsetX,
                    top: toolTipParams.coordinates.y,
                };
        }

        return toolTipParams.tooltipStyle;
    }
}
