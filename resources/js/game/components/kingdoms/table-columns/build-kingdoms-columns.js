import React from "react";
import { formatNumber } from "../../../lib/game/format-number";
export var buildKingdomsColumns = function (onClick) {
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
                        className:
                            "text-blue-500 dark:text-blue-400 hover:text-blue-600 dark:hover:text-blue-500",
                        onClick: function () {
                            return onClick(row);
                        },
                    },
                    iconsToShow(row),
                    " ",
                    row.name,
                    " ",
                    row.is_protected ? " (Protected) " : "",
                );
            },
        },
        {
            name: "Map",
            selector: function (row) {
                return row.game_map_name;
            },
        },
        {
            name: "X Position",
            selector: function (row) {
                return row.x_position;
            },
        },
        {
            name: "Y Position",
            selector: function (row) {
                return row.y_position;
            },
        },
        {
            name: "Current Morale",
            selector: function (row) {
                return row.current_morale;
            },
            cell: function (row) {
                return (row.current_morale * 100).toFixed(2) + "%";
            },
        },
        {
            name: "Treasury",
            selector: function (row) {
                return row.treasury;
            },
            cell: function (row) {
                return formatNumber(row.treasury);
            },
        },
        {
            name: "Gold Bars",
            selector: function (row) {
                return row.gold_bars;
            },
            cell: function (row) {
                return formatNumber(row.gold_bars);
            },
        },
    ];
};
var iconsToShow = function (kingdom) {
    var icons = [];
    if (kingdom.is_protected) {
        icons.push(
            React.createElement("i", {
                className:
                    "ra ra-heavy-shield text-blue-500 dark:text-blue-400",
            }),
        );
    }
    if (kingdom.is_under_attack) {
        icons.push(
            React.createElement("i", {
                className: "ra ra-axe text-red-500 dark:text-red-400",
            }),
        );
    }
    var anyMoving = kingdom.unitsInMovement.filter(function (unitMovement) {
        var anyMoving =
            unitMovement.is_returning ||
            unitMovement.is_moving ||
            unitMovement.is_recalled ||
            unitMovement.is_attacking;
        var fromThisKingdom = kingdom.name === unitMovement.from_kingdom_name;
        var toThisKingdom = kingdom.name === unitMovement.to_kingdom_name;
        return anyMoving && (fromThisKingdom || toThisKingdom);
    });
    if (anyMoving.length > 0) {
        if (anyMoving[0].is_attacking) {
            icons.push(
                React.createElement("i", {
                    className: "ra ra-axe text-red-500 dark:text-red-400",
                }),
            );
        }
        if (anyMoving[0].is_returning || anyMoving[0].is_recalled) {
            icons.push(
                React.createElement("i", {
                    className:
                        "fas fa-exchange-alt text-orange-500 dark:text-orange-300",
                }),
            );
        } else {
            icons.push(
                React.createElement("i", {
                    className:
                        "ra ra-heavy-shield text-blue-500 dark:text-blue-400",
                }),
            );
        }
    }
    return icons;
};
//# sourceMappingURL=build-kingdoms-columns.js.map
