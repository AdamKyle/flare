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
import React, { Fragment } from "react";
import BasicCard from "../../../components/ui/cards/basic-card";
import BuildingInformation from "./building-information";
import BuildingsTable from "./buildings-table";
var SmallBuildingsSection = (function (_super) {
    __extends(SmallBuildingsSection, _super);
    function SmallBuildingsSection(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            view_building: null,
        };
        return _this;
    }
    SmallBuildingsSection.prototype.viewSelectedBuilding = function (building) {
        this.setState({
            view_building: typeof building !== "undefined" ? building : null,
        });
    };
    SmallBuildingsSection.prototype.isInQueue = function () {
        if (this.state.view_building === null) {
            return false;
        }
        if (this.props.kingdom.building_queue.length === 0) {
            return false;
        }
        var self = this;
        return (
            this.props.kingdom.building_queue.filter(function (queue) {
                if (self.state.view_building !== null) {
                    return queue.building_id === self.state.view_building.id;
                }
            }).length > 0
        );
    };
    SmallBuildingsSection.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                "div",
                { className: "text-right cursor-pointer  text-red-500 mb-4" },
                React.createElement(
                    "button",
                    { onClick: this.props.close_selected },
                    React.createElement("i", {
                        className: "fas fa-minus-circle",
                    }),
                ),
            ),
            this.state.view_building !== null
                ? React.createElement(BuildingInformation, {
                      building: this.state.view_building,
                      close: this.viewSelectedBuilding.bind(this),
                      kingdom_building_time_reduction:
                          this.props.kingdom.building_time_reduction,
                      kingdom_building_cost_reduction:
                          this.props.kingdom.building_cost_reduction,
                      kingdom_iron_cost_reduction:
                          this.props.kingdom.iron_cost_reduction,
                      kingdom_population_cost_reduction:
                          this.props.kingdom.population_cost_reduction,
                      kingdom_current_population:
                          this.props.kingdom.current_population,
                      character_id: this.props.kingdom.character_id,
                      is_in_queue: this.isInQueue(),
                      character_gold: this.props.character_gold,
                      user_id: this.props.user_id,
                  })
                : React.createElement(
                      BasicCard,
                      null,
                      React.createElement(BuildingsTable, {
                          buildings: this.props.kingdom.buildings,
                          dark_tables: this.props.dark_tables,
                          view_building: this.viewSelectedBuilding.bind(this),
                          buildings_in_queue: this.props.kingdom.building_queue,
                          view_port: this.props.view_port,
                      }),
                  ),
        );
    };
    return SmallBuildingsSection;
})(React.Component);
export default SmallBuildingsSection;
//# sourceMappingURL=small-buildings-section.js.map
