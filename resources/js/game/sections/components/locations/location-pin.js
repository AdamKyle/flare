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
import React from "react";
var LocationPin = (function (_super) {
    __extends(LocationPin, _super);
    function LocationPin(props) {
        return _super.call(this, props) || this;
    }
    LocationPin.prototype.openLocationInformation = function (event) {
        this.props.openLocationDetails(
            parseInt(event.target.getAttribute("data-location-id")),
        );
    };
    LocationPin.prototype.render = function () {
        return React.createElement("button", {
            key: this.props.location.id,
            "data-location-id": this.props.location.id,
            className: this.props.pin_class,
            style: {
                top: this.props.location.y,
                left: this.props.location.x,
            },
            onClick: this.openLocationInformation.bind(this),
            onMouseEnter: this.props.onMouseEnter,
            onMouseLeave: this.props.onMouseLeave,
        });
    };
    return LocationPin;
})(React.Component);
export default LocationPin;
//# sourceMappingURL=location-pin.js.map
