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
import React, { Fragment } from "react";
import InfoAlert from "../../../../components/ui/alerts/simple-alerts/info-alert";
import MoveUnits from "../../unit-movement/move-units";
var UnitMovement = (function (_super) {
    __extends(UnitMovement, _super);
    function UnitMovement(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            selected_kingdoms: [],
            selected_units: [],
        };
        _this.moveUnits = new MoveUnits();
        return _this;
    }
    UnitMovement.prototype.setAmountToMove = function (
        kingdomId,
        unitId,
        unitAmount,
        e,
    ) {
        var _this = this;
        var unitsToCall = this.moveUnits.setAmountToMove(
            this.state.selected_units,
            kingdomId,
            unitId,
            unitAmount,
            e,
        );
        if (unitsToCall === null) {
            return;
        }
        this.setState(
            {
                selected_units: unitsToCall,
            },
            function () {
                _this.props.update_units_selected(unitsToCall);
            },
        );
    };
    UnitMovement.prototype.setKingdoms = function (data) {
        var _this = this;
        var validData = data.filter(function (data) {
            return data.value !== "Please select one or more kingdoms";
        });
        var selectedKingdoms = JSON.parse(
            JSON.stringify(this.state.selected_kingdoms),
        );
        selectedKingdoms = validData.map(function (value) {
            return parseInt(value.value, 10) || 0;
        });
        this.setState(
            {
                selected_kingdoms: selectedKingdoms,
            },
            function () {
                _this.props.update_kingdoms_selected(selectedKingdoms);
            },
        );
    };
    UnitMovement.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            this.props.kingdoms.length > 0
                ? React.createElement(
                      Fragment,
                      null,
                      this.moveUnits.renderKingdomSelect(
                          this.props.kingdoms,
                          this.state.selected_kingdoms,
                          this.setKingdoms.bind(this),
                      ),
                      this.state.selected_kingdoms.length > 0
                          ? React.createElement(
                                "div",
                                {
                                    className:
                                        "my-4 max-h-[350px] overflow-y-auto",
                                },
                                this.moveUnits.getUnitOptions(
                                    this.props.kingdoms,
                                    this.state.selected_units,
                                    this.state.selected_kingdoms,
                                    this.setAmountToMove.bind(this),
                                ),
                            )
                          : null,
                  )
                : React.createElement(
                      InfoAlert,
                      null,
                      "You have no units in other kingdoms to move units from or you have no other kingdoms.",
                  ),
        );
    };
    return UnitMovement;
})(React.Component);
export default UnitMovement;
//# sourceMappingURL=unit-movement.js.map
