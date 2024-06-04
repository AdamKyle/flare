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
import Tabs from "../../../components/ui/tabs/tabs";
import TabPanel from "../../../components/ui/tabs/tab-panel";
import BuildingsTable from "../buildings/buildings-table";
import UnitsTable from "../units/units-table";
import BasicCard from "../../../components/ui/cards/basic-card";
import React from "react";
import KingdomQueues from "../queues/kingdom-queues";
var KingdomTabs = (function (_super) {
    __extends(KingdomTabs, _super);
    function KingdomTabs(props) {
        var _this = _super.call(this, props) || this;
        _this.tabs = [
            {
                key: "buildings",
                name: "Buildings",
            },
            {
                key: "units",
                name: "Units",
            },
            {
                name: "Queues",
                key: "current-queue",
            },
        ];
        return _this;
    }
    KingdomTabs.prototype.render = function () {
        return React.createElement(
            BasicCard,
            null,
            React.createElement(
                Tabs,
                { tabs: this.tabs, full_width: true },
                React.createElement(
                    TabPanel,
                    { key: "buildings" },
                    React.createElement(BuildingsTable, {
                        buildings: this.props.kingdom.buildings,
                        dark_tables: this.props.dark_tables,
                        buildings_in_queue: this.props.kingdom.building_queue,
                        view_building: this.props.manage_view_building,
                        view_port: this.props.view_port,
                    }),
                ),
                React.createElement(
                    TabPanel,
                    { key: "units" },
                    React.createElement(UnitsTable, {
                        units: this.props.kingdom.units,
                        buildings: this.props.kingdom.buildings,
                        dark_tables: this.props.dark_tables,
                        view_unit: this.props.manage_view_unit,
                        units_in_queue: this.props.kingdom.unit_queue,
                        current_units: this.props.kingdom.current_units,
                    }),
                ),
                React.createElement(
                    TabPanel,
                    { key: "current-queue" },
                    React.createElement(KingdomQueues, {
                        user_id: this.props.user_id,
                        kingdom_id: this.props.kingdom.id,
                        character_id: this.props.kingdom.character_id,
                        kingdoms: this.props.kingdoms,
                    }),
                ),
            ),
        );
    };
    return KingdomTabs;
})(React.Component);
export default KingdomTabs;
//# sourceMappingURL=kingdom-tabs.js.map
