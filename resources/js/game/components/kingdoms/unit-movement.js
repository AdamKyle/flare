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
import UnitsMovementTable from "./unit-movement/units-movement-table";
var UnitMovement = (function (_super) {
    __extends(UnitMovement, _super);
    function UnitMovement(props) {
        return _super.call(this, props) || this;
    }
    UnitMovement.prototype.render = function () {
        return React.createElement(UnitsMovementTable, {
            units_in_movement: this.props.units_in_movement,
            dark_tables: this.props.dark_tables,
            character_id: this.props.character_id,
        });
    };
    return UnitMovement;
})(React.Component);
export default UnitMovement;
//# sourceMappingURL=unit-movement.js.map
