import React from "react";
import ItemNameColorationButton from "../../../../../components/items/item-name/item-name-coloration-button";
import { formatNumber } from "../../../format-number";
import PrimaryLinkButton from "../../../../../components/ui/buttons/primary-link-button";
export var BuildInventoryTableColumns = function (
    viewPort,
    component,
    clickAction,
    manageSkills,
    componentName,
    selectMultipleItems,
) {
    if (viewPort <= 639) {
        var smallerColumns = [
            {
                name: "Name",
                selector: function (row) {
                    return row.item_name;
                },
                cell: function (row) {
                    return React.createElement(
                        "span",
                        { className: "m-auto" },
                        React.createElement(ItemNameColorationButton, {
                            item: row,
                            on_click: clickAction,
                        }),
                    );
                },
            },
            {
                name: "Type",
                selector: function (row) {
                    return row.type;
                },
                sortable: true,
            },
        ];
        if (typeof component !== "undefined") {
            smallerColumns.push({
                name: "Actions",
                selector: function (row) {
                    return "";
                },
                cell: function (row) {
                    return component.actions(row);
                },
            });
        }
        if (typeof selectMultipleItems !== "undefined") {
            smallerColumns.unshift({
                name: "",
                selector: function (row) {
                    return row.slot_id;
                },
                cell: function (row) {
                    return React.createElement(
                        "span",
                        { className: "m-auto" },
                        React.createElement("input", {
                            type: "checkbox",
                            onChange: function (e) {
                                return selectMultipleItems(e);
                            },
                            className: "form-checkbox",
                            "data-slot-id": row.slot_id,
                        }),
                    );
                },
            });
        }
        return smallerColumns;
    }
    var columns = [
        {
            name: "Name",
            selector: function (row) {
                return row.item_name;
            },
            cell: function (row) {
                return React.createElement(
                    "span",
                    { className: "m-auto" },
                    React.createElement(ItemNameColorationButton, {
                        item: row,
                        on_click: clickAction,
                    }),
                );
            },
        },
        {
            name: "Type",
            selector: function (row) {
                return row.type;
            },
            sortable: true,
        },
        {
            name: "Attack",
            selector: function (row) {
                return row.attack;
            },
            sortable: true,
            format: function (row) {
                return formatNumber(row.attack);
            },
        },
        {
            name: "AC",
            selector: function (row) {
                return row.ac;
            },
            sortable: true,
            format: function (row) {
                return formatNumber(row.ac);
            },
        },
        {
            name: "Holy Stacks",
            selector: function (row) {
                return row.holy_stacks;
            },
            sortable: true,
            format: function (row) {
                return row.has_holy_stacks_applied + "/" + row.holy_stacks;
            },
        },
    ];
    if (typeof componentName !== "undefined") {
        if (componentName === "equipped") {
            columns.push({
                name: "Position",
                selector: function (row) {
                    return "";
                },
                cell: function (row) {
                    return row.position;
                },
            });
        }
    }
    if (typeof manageSkills !== "undefined") {
        columns.push({
            name: "Item Skills",
            selector: function (row) {
                return row.item_skill;
            },
            cell: function (row) {
                return React.createElement(
                    "span",
                    null,
                    row.item_skill_progressions.length > 0
                        ? React.createElement(PrimaryLinkButton, {
                              button_label: "Manage Skills",
                              on_click: function () {
                                  return manageSkills(
                                      row.slot_id,
                                      row.item_skills,
                                      row.item_skill_progressions,
                                  );
                              },
                          })
                        : "N/A",
                );
            },
        });
    }
    if (typeof component !== "undefined") {
        columns.push({
            name: "Actions",
            selector: function (row) {
                return "";
            },
            cell: function (row) {
                return component.actions(row);
            },
        });
    }
    if (typeof selectMultipleItems !== "undefined") {
        columns.unshift({
            name: "Select Item(s)",
            selector: function (row) {
                return row.slot_id;
            },
            cell: function (row) {
                return React.createElement(
                    "span",
                    { className: "m-auto" },
                    React.createElement("input", {
                        type: "checkbox",
                        onChange: function (e) {
                            return selectMultipleItems(e);
                        },
                        className:
                            "form-checkbox w-4 h-4 text-blue-600 focus:ring-2 focus:ring-offset-2 focus:ring-blue-700 dark:focus:ring-blue-500",
                        "aria-label": "Select one or items",
                        "aria-describedby":
                            "allows you to select one or more items to then do additional actions with.",
                        "data-slot-id": row.slot_id,
                    }),
                );
            },
        });
    }
    return columns;
};
export var buildLimitedColumns = function (
    viewPort,
    component,
    onClick,
    usableItem,
) {
    if (viewPort <= 639) {
        var columns_1 = [
            {
                name: "Name",
                selector: function (row) {
                    return row.item_name;
                },
                cell: function (row) {
                    return React.createElement(ItemNameColorationButton, {
                        item: row,
                        on_click: onClick,
                    });
                },
            },
        ];
        if (usableItem) {
            columns_1.push({
                name: "Can Stack",
                selector: function (row) {
                    return "";
                },
                cell: function (row) {
                    return React.createElement(
                        "span",
                        null,
                        row.can_stack ? "Yes" : "No",
                    );
                },
            });
        } else {
            columns_1.push({
                name: "Description",
                selector: function (row) {
                    return row.description;
                },
                cell: function (row) {
                    return row.description;
                },
            });
        }
        if (typeof component !== "undefined") {
            columns_1.push({
                name: "Actions",
                selector: function (row) {
                    return "";
                },
                cell: function (row) {
                    return component.actions(row);
                },
            });
        }
        return columns_1;
    }
    var columns = [
        {
            name: "Name",
            selector: function (row) {
                return row.item_name;
            },
            cell: function (row) {
                return React.createElement(ItemNameColorationButton, {
                    item: row,
                    on_click: onClick,
                });
            },
        },
        {
            name: "Description",
            selector: function (row) {
                return row.description;
            },
            cell: function (row) {
                return row.description;
            },
        },
    ];
    if (usableItem) {
        columns.push({
            name: "Can Stack",
            selector: function (row) {
                return "";
            },
            cell: function (row) {
                return row.can_stack ? "Yes" : "No";
            },
        });
    }
    if (typeof component !== "undefined") {
        columns.push({
            name: "Actions",
            selector: function (row) {
                return "";
            },
            cell: function (row) {
                return component.actions(row);
            },
        });
    }
    return columns;
};
export var buildGemColumns = function (component, onClick) {
    var columns = [
        {
            name: "Name",
            selector: function (row) {
                return row.name;
            },
            cell: function (row) {
                return React.createElement(
                    "button",
                    {
                        className: "text-lime-600 dark:text-lime-500",
                        onClick: function () {
                            return onClick(row);
                        },
                    },
                    row.name,
                );
            },
        },
        {
            name: "Tier",
            selector: function (row) {
                return row.tier;
            },
            cell: function (row) {
                return row.tier;
            },
        },
        {
            name: "Amount",
            selector: function (row) {
                return row.amount;
            },
            cell: function (row) {
                return row.amount;
            },
        },
        {
            name: "Atoned To",
            selector: function (row) {
                return row.element_atoned_to;
            },
            cell: function (row) {
                return row.element_atoned_to;
            },
        },
        {
            name: "Atoned Amount",
            selector: function (row) {
                return row.element_atoned_to_amount;
            },
            cell: function (row) {
                return (row.element_atoned_to_amount * 100).toFixed(2) + "%";
            },
        },
        {
            name: "Strong Against",
            selector: function (row) {
                return row.strong_against;
            },
            cell: function (row) {
                return row.strong_against;
            },
        },
        {
            name: "Weak Against",
            selector: function (row) {
                return row.weak_against;
            },
            cell: function (row) {
                return row.weak_against;
            },
        },
    ];
    if (typeof component !== "undefined") {
        columns.push({
            name: "Actions",
            selector: function (row) {
                return "";
            },
            cell: function (row) {
                return component.gemActions(row);
            },
        });
    }
    return columns;
};
//# sourceMappingURL=build-inventory-table-columns.js.map
