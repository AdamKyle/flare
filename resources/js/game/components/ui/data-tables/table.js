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
import DataTable from "react-data-table-component";
var Table = (function (_super) {
    __extends(Table, _super);
    function Table(props) {
        return _super.call(this, props) || this;
    }
    Table.prototype.render = function () {
        return React.createElement(
            "div",
            null,
            React.createElement(DataTable, {
                columns: this.props.columns,
                data: this.props.data,
                theme: this.props.dark_table ? "dark" : "default",
                conditionalRowStyles:
                    typeof this.props.conditional_row_styles === "undefined"
                        ? []
                        : this.props.conditional_row_styles,
                pagination: true,
                responsive: true,
            }),
        );
    };
    return Table;
})(React.Component);
export default Table;
//# sourceMappingURL=table.js.map
