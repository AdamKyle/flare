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
var KingdomPin = (function (_super) {
    __extends(KingdomPin, _super);
    function KingdomPin(props) {
        return _super.call(this, props) || this;
    }
    KingdomPin.prototype.kingdomStyle = function () {
        return {
            top: this.props.kingdom.y_position,
            left: this.props.kingdom.x_position,
            "--kingdom-color": this.props.kingdom.color,
        };
    };
    KingdomPin.prototype.openKingdomModal = function (e) {
        this.props.open_kingdom_modal(
            parseInt(e.target.getAttribute("data-kingdom-id")),
        );
    };
    KingdomPin.prototype.render = function () {
        return React.createElement("button", {
            role: "button",
            key:
                Math.random().toString(36).substring(7) +
                "-" +
                this.props.kingdom.id,
            "data-kingdom-id": this.props.kingdom.id,
            className: "kingdom-x-pin",
            style: this.kingdomStyle(),
            onClick: this.openKingdomModal.bind(this),
        });
    };
    return KingdomPin;
})(React.Component);
export default KingdomPin;
//# sourceMappingURL=kingdom-pin.js.map
