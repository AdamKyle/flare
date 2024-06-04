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
import React, { Fragment } from "react";
import Select from "react-select";
import { formatNumber } from "../../../lib/game/format-number";
var MoveUnits = (function () {
    function MoveUnits() {}
    MoveUnits.prototype.getKingdomSelectionOptions = function (kingdoms) {
        return kingdoms.map(function (kingdom) {
            return {
                label: kingdom.kingdom_name,
                value: kingdom.kingdom_id.toString(),
            };
        });
    };
    MoveUnits.prototype.setAmountToMove = function (
        selectedUnits,
        kingdomId,
        unitId,
        unitAmount,
        e,
    ) {
        var unitsToCall = JSON.parse(JSON.stringify(selectedUnits));
        var index = unitsToCall.findIndex(function (unitToCall) {
            return (
                unitToCall.kingdom_id === kingdomId &&
                unitToCall.unit_id === unitId
            );
        });
        var amount = parseInt(e.target.value, 10) || 0;
        if (amount <= 0) {
            amount = 0;
        }
        if (amount > unitAmount) {
            amount = unitAmount;
        }
        if (index === -1) {
            if (amount === 0) {
                return null;
            }
            unitsToCall.push({
                kingdom_id: kingdomId,
                unit_id: unitId,
                amount: amount > unitAmount ? unitAmount : amount,
            });
        } else {
            if (amount === 0) {
                unitsToCall.splice(index, 1);
                return unitsToCall;
            }
            unitsToCall[index].amount =
                amount > unitAmount ? unitAmount : amount;
        }
        return unitsToCall;
    };
    MoveUnits.prototype.getValueOfUnitsToCall = function (
        selectedUnits,
        kingdomId,
        unitId,
    ) {
        var unitsToCall = JSON.parse(JSON.stringify(selectedUnits));
        var index = unitsToCall.findIndex(function (unitToCall) {
            return (
                unitToCall.kingdom_id === kingdomId &&
                unitToCall.unit_id === unitId
            );
        });
        if (index === -1) {
            return "";
        }
        return unitsToCall[index].amount;
    };
    MoveUnits.prototype.getUnitOptions = function (
        kingdoms,
        selectedUnits,
        selectedKingdoms,
        setAmountToMove,
    ) {
        var kingdomsWithUnits = kingdoms.filter(function (kingdom) {
            if (selectedKingdoms.includes(kingdom.kingdom_id)) {
                return kingdom;
            }
        });
        var self = this;
        var units = kingdomsWithUnits.map(function (kingdom, kingdomIndex) {
            return kingdom.units.map(function (unit, index) {
                return React.createElement(
                    "div",
                    { key: kingdom.kingdom_id + "-" + unit.id },
                    index === 0
                        ? React.createElement(
                              "p",
                              { className: "my-2" },
                              "From Kingdom: ",
                              kingdom.kingdom_name,
                              " and will take:",
                              " ",
                              self.getTimeToTravel(kingdom.time),
                              " to get to this kingdom",
                          )
                        : null,
                    React.createElement(
                        "div",
                        { className: "flex items-center my-4" },
                        React.createElement(
                            "label",
                            { className: "w-1/2" },
                            unit.name,
                            " Amount to move",
                        ),
                        React.createElement(
                            "div",
                            { className: "w-1/2" },
                            React.createElement("input", {
                                type: "number",
                                value: self.getValueOfUnitsToCall(
                                    selectedUnits,
                                    kingdom.kingdom_id,
                                    unit.id,
                                ),
                                onChange: function (e) {
                                    return setAmountToMove(
                                        kingdom.kingdom_id,
                                        unit.id,
                                        unit.amount,
                                        e,
                                    );
                                },
                                className: "form-control",
                            }),
                            React.createElement(
                                "span",
                                {
                                    className:
                                        "text-gray-700 dark:text-white text-xs",
                                },
                                "Max amount to recruit:",
                                " ",
                                formatNumber(unit.amount),
                            ),
                        ),
                    ),
                    kingdom.units.length === index + 1 &&
                        kingdomsWithUnits.length > 1 &&
                        kingdomsWithUnits.length !== kingdomIndex + 1
                        ? React.createElement("div", {
                              className:
                                  "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6",
                          })
                        : null,
                );
            });
        });
        return units;
    };
    MoveUnits.prototype.renderKingdomSelect = function (
        kingdoms,
        selectedKingdoms,
        setKingdoms,
    ) {
        return React.createElement(
            Fragment,
            null,
            React.createElement(Select, {
                onChange: setKingdoms,
                isMulti: true,
                options: this.getKingdomSelectionOptions(kingdoms),
                menuPosition: "absolute",
                menuPlacement: "bottom",
                styles: {
                    menuPortal: function (base) {
                        return __assign(__assign({}, base), {
                            zIndex: 9999,
                            color: "#000000",
                        });
                    },
                },
                menuPortalTarget: document.body,
                value: this.getSelectedKingdomsValue(
                    kingdoms,
                    selectedKingdoms,
                ),
            }),
        );
    };
    MoveUnits.prototype.getSelectedKingdomsValue = function (
        kingdoms,
        selectedKingdoms,
    ) {
        var foundKingdoms = selectedKingdoms.map(function (kingdom) {
            var index = kingdoms.findIndex(function (kingdomData) {
                return kingdomData.kingdom_id === kingdom;
            });
            if (index !== -1) {
                return {
                    label: kingdoms[index].kingdom_name,
                    value: kingdoms[index].kingdom_id.toString(),
                };
            }
        });
        if (foundKingdoms.length > 0) {
            return foundKingdoms;
        }
        return [
            {
                label: "Please select one or more kingdoms",
                value: "Please select one or more kingdoms",
            },
        ];
    };
    MoveUnits.prototype.getTimeToTravel = function (time) {
        var hours = time / 60;
        if (hours >= 1) {
            return " roughly " + hours.toFixed(0) + " hour(s) ";
        }
        return time + " minute(s) ";
    };
    return MoveUnits;
})();
export default MoveUnits;
//# sourceMappingURL=move-units.js.map
