import clsx from "clsx";
import { DateTime } from "luxon";
import React, { Fragment } from "react";
import { formatNumber } from "../../../lib/game/format-number";
import TimerProgressBar from "../../ui/progress-bars/timer-progress-bar";
export var buildBuildingsColumns = function (
    onClick,
    cancelBuilding,
    buildingsInQueue,
    viewPort,
) {
    return [
        {
            name: "Name",
            selector: function (row) {
                return row.name;
            },
            cell: function (row) {
                return React.createElement(
                    "button",
                    {
                        onClick: function () {
                            return onClick(row);
                        },
                        className: clsx({
                            "text-blue-500 dark:text-blue-400 hover:text-blue-600 dark:hover:text-blue-500":
                                !row.is_locked,
                            "text-white underline": row.is_locked,
                        }),
                    },
                    row.name,
                );
            },
        },
        {
            name: "Level",
            selector: function (row) {
                return row.level;
            },
            cell: function (row) {
                return React.createElement(
                    "span",
                    null,
                    row.level,
                    "/",
                    row.max_level,
                );
            },
        },
        {
            name: "Defence",
            selector: function (row) {
                return row.current_defence;
            },
            cell: function (row) {
                return formatNumber(row.current_defence);
            },
        },
        {
            name: "Durability",
            selector: function (row) {
                return row.current_durability;
            },
            cell: function (row) {
                return React.createElement(
                    "span",
                    null,
                    row.current_durability,
                    "/",
                    row.max_durability,
                );
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
                        { className: "w-full mt-2" },
                        React.createElement(TimerProgressBar, {
                            time_remaining: fetchTimeRemaining(
                                row.id,
                                buildingsInQueue,
                            ),
                            time_out_label: "Building",
                            useSmallTimer: viewPort < 800,
                        }),
                        fetchTimeRemaining(row.id, buildingsInQueue) > 0
                            ? React.createElement(
                                  "div",
                                  { className: "mb-2 mt-4" },
                                  React.createElement(
                                      "button",
                                      {
                                          className:
                                              "hover:text-red-500 text-red-700 dark:text-red-500 dark:hover:text-red-400 " +
                                              "disabled:text-red-400 dark:disabled:bg-red-400 disabled:line-through " +
                                              "focus:outline-none focus-visible:ring-2 focus-visible:ring-red-200 dark:focus-visible:ring-white " +
                                              "focus-visible:ring-opacity-75",
                                          onClick: function () {
                                              return cancelBuilding(
                                                  findBuildingInQueue(
                                                      row.id,
                                                      buildingsInQueue,
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
            omit: buildingsInQueue.length === 0,
        },
    ];
};
var findBuildingInQueue = function (buildingId, buildingsInQueue) {
    var foundBuilding = buildingsInQueue.filter(function (building) {
        return building.building_id === buildingId;
    });
    if (foundBuilding.length > 0) {
        var buildingInQueue = foundBuilding[0];
        return buildingInQueue.id;
    }
    return null;
};
var fetchTimeRemaining = function (buildingId, buildingsInQueue) {
    var foundBuilding = buildingsInQueue.filter(function (building) {
        return building.building_id === buildingId;
    });
    if (foundBuilding.length > 0) {
        var buildingInQueue = foundBuilding[0];
        var start = DateTime.now();
        var end = DateTime.fromISO(buildingInQueue.completed_at);
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
//# sourceMappingURL=build-buildings-columns.js.map
