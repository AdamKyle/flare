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
import UserStatistics from "./user-statistics/user-statistics";
var Dashboard = (function (_super) {
    __extends(Dashboard, _super);
    function Dashboard(props) {
        return _super.call(this, props) || this;
    }
    Dashboard.prototype.render = function () {
        return React.createElement(UserStatistics, null);
    };
    return Dashboard;
})(React.Component);
export default Dashboard;
//# sourceMappingURL=dashboard.js.map
