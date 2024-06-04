import React, { Fragment } from "react";
import DangerButton from "../../ui/buttons/danger-button";
export var buildLogsColumns = function (onClick, deleteLog) {
    return [
        {
            name: "Title",
            selector: function (row) {
                return row.status;
            },
            cell: function (row) {
                return React.createElement(
                    "button",
                    {
                        className:
                            "text-blue-500 dark:text-blue-400 hover:text-blue-600 dark:hover:text-blue-500",
                        onClick: function () {
                            return onClick(row);
                        },
                    },
                    row.status,
                );
            },
        },
        {
            name: "Read",
            cell: function (row) {
                return React.createElement(
                    "span",
                    null,
                    row.opened
                        ? React.createElement(
                              Fragment,
                              null,
                              React.createElement("i", {
                                  className: "far fa-envelope-open mr-2",
                              }),
                              " Read",
                          )
                        : React.createElement(
                              Fragment,
                              null,
                              React.createElement("i", {
                                  className: "far fa-envelope mr-2",
                              }),
                              " Not Read",
                          ),
                );
            },
        },
        {
            name: "Kingdom Attacked",
            selector: function (row) {
                return row.to_kingdom_name;
            },
        },
        {
            name: "Attacked From",
            selector: function (row) {
                return row.from_kingdom_name === null
                    ? "N/A"
                    : row.from_kingdom_name;
            },
        },
        {
            name: "Created At",
            selector: function (row) {
                return row.created_at;
            },
        },
        {
            name: "Actions",
            selector: function (row) {
                return row.status;
            },
            cell: function (row) {
                return React.createElement(DangerButton, {
                    button_label: "Delete Log",
                    on_click: function () {
                        return deleteLog(row);
                    },
                });
            },
        },
    ];
};
//# sourceMappingURL=build-logs-columns.js.map
