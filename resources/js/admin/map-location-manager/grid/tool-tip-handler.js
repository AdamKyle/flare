var __assign =
    (this && this.__assign) ||
    function () {
        __assign =
            Object.assign ||
            function (t) {
                for (var s, i = 1, n = arguments.length; i < n; i++) {
                    s = arguments[i];
                    for (var p in s)
                        if (Object.prototype.hasOwnProperty.call(s, p))
                            t[p] = s[p];
                }
                return t;
            };
        return __assign.apply(this, arguments);
    };
var ToolTipHandler = (function () {
    function ToolTipHandler() {
        this.tooltipStyle = {
            position: "absolute",
            background: "rgba(0, 0, 0, 0.5)",
            color: "white",
            padding: "5px",
            borderRadius: "3px",
            pointerEvents: "none",
            zIndex: 999,
        };
    }
    ToolTipHandler.prototype.getOffSet = function (
        position,
        coordinates,
        showTooltip,
    ) {
        var styles = __assign(__assign({}, this.tooltipStyle), {
            visibility: showTooltip ? "visible" : "hidden",
        });
        var params = {
            tooltipPosition: position,
            tooltipStyle: styles,
            coordinates: coordinates,
            tooltipOffsetX: 10,
            tooltipOffsetY: 10,
        };
        return this.buildToolTipOffSet(params);
    };
    ToolTipHandler.prototype.buildToolTipOffSet = function (toolTipParams) {
        switch (toolTipParams.tooltipPosition) {
            case "top-left":
                toolTipParams.tooltipStyle = __assign(
                    __assign({}, toolTipParams.tooltipStyle),
                    {
                        left:
                            toolTipParams.coordinates.x +
                            toolTipParams.tooltipOffsetX,
                        top:
                            toolTipParams.coordinates.y +
                            toolTipParams.tooltipOffsetY,
                    },
                );
                break;
            case "top-right":
                toolTipParams.tooltipStyle = __assign(
                    __assign({}, toolTipParams.tooltipStyle),
                    {
                        left: toolTipParams.coordinates.x - 70,
                        top:
                            toolTipParams.coordinates.y +
                            toolTipParams.tooltipOffsetY,
                    },
                );
                break;
            case "bottom-left":
                toolTipParams.tooltipStyle = __assign(
                    __assign({}, toolTipParams.tooltipStyle),
                    {
                        left:
                            toolTipParams.coordinates.x +
                            toolTipParams.tooltipOffsetX,
                        top:
                            toolTipParams.coordinates.y -
                            toolTipParams.tooltipOffsetY,
                    },
                );
                break;
            case "bottom-right":
                toolTipParams.tooltipStyle = __assign(
                    __assign({}, toolTipParams.tooltipStyle),
                    {
                        left: toolTipParams.coordinates.x - 70,
                        top:
                            toolTipParams.coordinates.y -
                            toolTipParams.tooltipOffsetY,
                    },
                );
                break;
            case "bottom":
                toolTipParams.tooltipStyle = __assign(
                    __assign({}, toolTipParams.tooltipStyle),
                    {
                        left: toolTipParams.coordinates.x,
                        top:
                            toolTipParams.coordinates.y -
                            toolTipParams.tooltipOffsetY,
                    },
                );
                break;
            case "left":
            case "left-top":
            case "left-bottom":
                toolTipParams.tooltipStyle = __assign(
                    __assign({}, toolTipParams.tooltipStyle),
                    {
                        left:
                            toolTipParams.coordinates.x +
                            toolTipParams.tooltipOffsetX,
                        top: toolTipParams.coordinates.y,
                    },
                );
                break;
            case "right":
            case "right-top":
            case "right-bottom":
                toolTipParams.tooltipStyle = __assign(
                    __assign({}, toolTipParams.tooltipStyle),
                    {
                        left: toolTipParams.coordinates.x - 40,
                        top: toolTipParams.coordinates.y,
                    },
                );
                break;
            default:
                toolTipParams.tooltipStyle = __assign(
                    __assign({}, toolTipParams.tooltipStyle),
                    {
                        left:
                            toolTipParams.coordinates.x +
                            toolTipParams.tooltipOffsetX,
                        top: toolTipParams.coordinates.y,
                    },
                );
        }
        return toolTipParams.tooltipStyle;
    };
    return ToolTipHandler;
})();
export default ToolTipHandler;
//# sourceMappingURL=tool-tip-handler.js.map
