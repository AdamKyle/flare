var __decorate =
    (this && this.__decorate) ||
    function (decorators, target, key, desc) {
        var c = arguments.length,
            r =
                c < 3
                    ? target
                    : desc === null
                      ? (desc = Object.getOwnPropertyDescriptor(target, key))
                      : desc,
            d;
        if (
            typeof Reflect === "object" &&
            typeof Reflect.decorate === "function"
        )
            r = Reflect.decorate(decorators, target, key, desc);
        else
            for (var i = decorators.length - 1; i >= 0; i--)
                if ((d = decorators[i]))
                    r =
                        (c < 3
                            ? d(r)
                            : c > 3
                              ? d(target, key, r)
                              : d(target, key)) || r;
        return c > 3 && r && Object.defineProperty(target, key, r), r;
    };
var __metadata =
    (this && this.__metadata) ||
    function (k, v) {
        if (
            typeof Reflect === "object" &&
            typeof Reflect.metadata === "function"
        )
            return Reflect.metadata(k, v);
    };
import { injectable } from "tsyringe";
var MouseHandlers = (function () {
    function MouseHandlers() {
        var _this = this;
        this.handleLocationMouseEnter = function (x, y) {
            if (!_this.component) {
                throw new Error(
                    "Component is not registered. Call initialize first.",
                );
            }
            _this.component.setState({
                coordinates: { x: x, y: y },
                snapped: true,
                hoveredGridCell: { x: x, y: y },
            });
        };
        this.handleLocationMouseLeave = function () {
            if (!_this.component) {
                throw new Error(
                    "Component is not registered. Call initialize first.",
                );
            }
            _this.component.setState({
                snapped: false,
                hoveredGridCell: { x: null, y: null },
            });
        };
        this.handleMouseMove = this.handleMouseMove.bind(this);
        this.handleMouseLeave = this.handleMouseLeave.bind(this);
    }
    MouseHandlers.prototype.initialize = function (component) {
        this.component = component;
        return this;
    };
    MouseHandlers.prototype.handleMouseMove = function (e) {
        if (!this.component) {
            throw new Error(
                "Component is not registered. Call initialize first.",
            );
        }
        if (this.component.state.showModal) {
            return;
        }
        var clientX = e.clientX,
            clientY = e.clientY;
        var _a = e.currentTarget.getBoundingClientRect(),
            left = _a.left,
            top = _a.top;
        var mouseX = clientX - left;
        var mouseY = clientY - top;
        var _b = this.component.props.coordinates,
            xCoords = _b.x,
            yCoords = _b.y;
        var closestX = xCoords.reduce(function (prev, curr) {
            return Math.abs(curr - mouseX) < Math.abs(prev - mouseX)
                ? curr
                : prev;
        });
        var closestY = yCoords.reduce(function (prev, curr) {
            return Math.abs(curr - mouseY) < Math.abs(prev - mouseY)
                ? curr
                : prev;
        });
        this.component.setState({
            coordinates: { x: closestX, y: closestY },
            showTooltip: true,
            tooltipPosition: this.getTooltipPosition(closestX, closestY),
            hoveredGridCell: { x: closestX, y: closestY },
            snapped: true,
        });
    };
    MouseHandlers.prototype.handleGridCellMouseEnter = function (x, y) {
        if (!this.component) {
            throw new Error(
                "Component is not registered. Call initialize first.",
            );
        }
        var _a = this.component.props.coordinates,
            xCoords = _a.x,
            yCoords = _a.y;
        var closestX = xCoords.reduce(function (prev, curr) {
            return Math.abs(curr - x) < Math.abs(prev - x) ? curr : prev;
        });
        var closestY = yCoords.reduce(function (prev, curr) {
            return Math.abs(curr - y) < Math.abs(prev - y) ? curr : prev;
        });
        this.component.setState({
            coordinates: { x: closestX, y: closestY },
            showTooltip: true,
            tooltipPosition: this.getTooltipPosition(closestX, closestY),
            snapped: true,
        });
    };
    MouseHandlers.prototype.handleMouseLeave = function () {
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
    };
    MouseHandlers.prototype.getTooltipPosition = function (x, y) {
        if (!this.component) {
            throw new Error(
                "Component is not registered. Call initialize first.",
            );
        }
        var _a = this.component.props.coordinates,
            xCoords = _a.x,
            yCoords = _a.y;
        var width = xCoords.length > 0 ? xCoords[xCoords.length - 1] : 0;
        var height = yCoords.length > 0 ? yCoords[yCoords.length - 1] : 0;
        var isTop = y < height / 2;
        var isLeft = x < width / 2;
        var tooltipPosition = "";
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
    };
    MouseHandlers = __decorate(
        [injectable(), __metadata("design:paramtypes", [])],
        MouseHandlers,
    );
    return MouseHandlers;
})();
export default MouseHandlers;
//# sourceMappingURL=mouse-handlers.js.map
