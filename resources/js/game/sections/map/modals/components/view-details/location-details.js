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
import Dialogue from "../../../../../components/ui/dialogue/dialogue";
import LocationInformation from "../../../../components/locations/modals/location-details";
var LocationDetails = (function (_super) {
    __extends(LocationDetails, _super);
    function LocationDetails(props) {
        return _super.call(this, props) || this;
    }
    LocationDetails.prototype.buildTitle = function () {
        var location = this.props.location;
        return location.name + " (X/Y): " + location.x + "/" + location.y;
    };
    LocationDetails.prototype.render = function () {
        return React.createElement(
            Dialogue,
            {
                is_open: true,
                handle_close: this.props.handle_close,
                title: this.buildTitle(),
            },
            React.createElement(LocationInformation, {
                location: this.props.location,
            }),
        );
    };
    return LocationDetails;
})(React.Component);
export default LocationDetails;
//# sourceMappingURL=location-details.js.map
