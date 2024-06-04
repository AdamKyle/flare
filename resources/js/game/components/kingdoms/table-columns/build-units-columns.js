import clsx from "clsx";
import { DateTime } from "luxon";
import React, { Fragment } from "react";
import { formatNumber } from "../../../lib/game/format-number";
import TimerProgressBar from "../../ui/progress-bars/timer-progress-bar";
export var BuildUnitsColumns = function (
    onClick,
    cancelUnitRecruitment,
    unitsInQueue,
    currentUnits,
    buildings,
) {
    return [
        {
            name: "Name",
            selector: function (row) {
                return row.name;
            },
            cell: function (row) {
                return React.createElement(
                    "span",
                    { className: "m-auto" },
                    React.createElement(
                        "button",
                        {
                            onClick: function () {
                                return onClick(row);
                            },
                            className: clsx({
                                "text-blue-500 dark:text-blue-400 hover:text-blue-600 dark:hover:text-blue-500":
                                    !cannotBeRecruited(row, buildings),
                                "text-white underline": cannotBeRecruited(
                                    row,
                                    buildings,
                                ),
                            }),
                        },
                        row.name,
                    ),
                );
            },
        },
        {
            name: "Recruited From",
            selector: function (row) {
                return row.recruited_from.building_name;
            },
        },
        {
            name: "Amount",
            cell: function (row) {
                return renderAmount(row.id, currentUnits);
            },
        },
        {
            name: "Attack",
            selector: function (row) {
                return row.attack;
            },
            cell: function (row) {
                return React.createElement("span", null, row.attack);
            },
        },
        {
            name: "Defence",
            selector: function (row) {
                return row.defence;
            },
            cell: function (row) {
                return formatNumber(row.defence);
            },
        },
        {
            name: "Upgrade Time Left",
            minWidth: "300px",
            cell: function (row) {
                return React.createElement(
                    Fragment,
                    null,
                    React.createElement(
                        "div",
                        { className: "w-full mt-4" },
                        React.createElement(TimerProgressBar, {
                            time_remaining: fetchTimeRemaining(
                                row.id,
                                unitsInQueue,
                            ),
                            time_out_label: "Training",
                        }),
                        fetchTimeRemaining(row.id, unitsInQueue) > 0
                            ? React.createElement(
                                  "div",
                                  { className: "mt-2 mb-4" },
                                  React.createElement(
                                      "button",
                                      {
                                          className:
                                              "hover:text-red-500 text-red-700 dark:text-red-500 dark:hover:text-red-400 " +
                                              "disabled:text-red-400 dark:disabled:bg-red-400 disabled:line-through " +
                                              "focus:outline-none focus-visible:ring-2 focus-visible:ring-red-200 dark:focus-visible:ring-white " +
                                              "focus-visible:ring-opacity-75",
                                          onClick: function () {
                                              return cancelUnitRecruitment(
                                                  findUnitInQueue(
                                                      row.id,
                                                      unitsInQueue,
                                                  ),
                                              );
                                          },
                                      },
                                      "Cancel",
                                  ),
                              )
                            : null,
                    ),
                );
            },
            omit: unitsInQueue.length === 0,
        },
    ];
};
var cannotBeRecruited = function (unit, buildings) {
    var building = buildings.filter(function (building) {
        return (
            building.game_building_id === unit.recruited_from.game_building_id
        );
    });
    if (building.length === 0) {
        return false;
    }
    var foundBuilding = building[0];
    return (
        foundBuilding.level < unit.required_building_level ||
        foundBuilding.is_locked
    );
};
var findUnitInQueue = function (unitId, unitsInQueue) {
    var foundQueue = unitsInQueue.filter(function (queue) {
        return queue.game_unit_id === unitId;
    });
    if (foundQueue.length > 0) {
        var queue = foundQueue[0];
        return queue.id;
    }
    return null;
};
var renderAmount = function (unitId, currentUnits) {
    var foundUnitDetails = currentUnits.filter(function (unit) {
        return unit.game_unit_id === unitId;
    });
    if (foundUnitDetails.length > 0) {
        var unitDetails = foundUnitDetails[0];
        return formatNumber(unitDetails.amount);
    }
    return 0;
};
var fetchTimeRemaining = function (unitId, unitsInQueue) {
    var foundUnit = unitsInQueue.filter(function (unit) {
        return unit.game_unit_id === unitId;
    });
    if (foundUnit.length > 0) {
        var unitInQueue = foundUnit[0];
        var start = DateTime.now();
        var end = DateTime.fromISO(unitInQueue.completed_at);
        var difference = end.diff(start, ["seconds"]);
        if (typeof difference === "undefined") {
            return 0;
        }
        if (typeof difference.seconds === "undefined") {
            return 0;
        }
        return parseInt(difference.seconds.toFixed(0));
    }
    return 0;
};
//# sourceMappingURL=build-units-columns.js.map
