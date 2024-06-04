import React, { Fragment } from "react";
import TimerProgressBar from "../../ui/progress-bars/timer-progress-bar";
export var BuildUnitsInMovementColumns = function (
    cancelUnitMovement,
    unitsInMovement,
) {
    return [
        {
            name: "From Kingdom",
            selector: function (row) {
                return row.from_kingdom_name;
            },
        },
        {
            name: "To Kingdom",
            selector: function (row) {
                return row.to_kingdom_name;
            },
        },
        {
            name: "Reason",
            selector: function (row) {
                return row.reason;
            },
        },
        {
            name: "Time till arrival",
            cell: function (row) {
                return React.createElement(
                    Fragment,
                    null,
                    React.createElement(
                        "div",
                        { className: "w-full mt-4" },
                        React.createElement(TimerProgressBar, {
                            time_remaining: row.time_left,
                            time_out_label: "",
                            additional_css:
                                row.is_recalled || row.is_returning
                                    ? "mt-[-35px]"
                                    : "",
                        }),
                        row.time_left > 0 &&
                            !row.is_returning &&
                            !row.is_recalled
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
                                              return cancelUnitMovement(row.id);
                                          },
                                      },
                                      "Cancel",
                                  ),
                              )
                            : null,
                    ),
                );
            },
            omit: unitsInMovement.length === 0,
        },
    ];
};
//# sourceMappingURL=build-units-in-movement-columns.js.map
