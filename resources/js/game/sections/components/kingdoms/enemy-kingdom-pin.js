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
var EnemyKingdomPin = (function (_super) {
    __extends(EnemyKingdomPin, _super);
    function EnemyKingdomPin(props) {
        return _super.call(this, props) || this;
    }
    EnemyKingdomPin.prototype.kingdomStyle = function () {
        return {
            top: this.props.kingdom.y_position,
            left: this.props.kingdom.x_position,
            "--kingdom-color": this.props.color,
        };
    };
    EnemyKingdomPin.prototype.openKingdomModal = function (e) {
        this.props.open_kingdom_modal(
            parseInt(e.target.getAttribute("data-kingdom-id")),
        );
    };
    EnemyKingdomPin.prototype.render = function () {
        return React.createElement("div", {
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
    return EnemyKingdomPin;
})(React.Component);
export default EnemyKingdomPin;
//# sourceMappingURL=enemy-kingdom-pin.js.map
