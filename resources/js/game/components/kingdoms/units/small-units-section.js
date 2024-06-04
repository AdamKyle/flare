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
import UnitInformation from "./unit-information";
import UnitsTable from "./units-table";
var SmallUnitsSection = (function (_super) {
    __extends(SmallUnitsSection, _super);
    function SmallUnitsSection(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            unit_to_view: null,
        };
        return _this;
    }
    SmallUnitsSection.prototype.closeSection = function () {
        this.setState({
            unit_to_view: null,
        });
    };
    SmallUnitsSection.prototype.viewSelectedBuilding = function (unit) {
        this.setState({
            unit_to_view: typeof unit !== "undefined" ? unit : null,
        });
    };
    SmallUnitsSection.prototype.isUnitInQueue = function () {
        var _this = this;
        if (this.state.unit_to_view === null) {
            return false;
        }
        if (this.props.kingdom.unit_queue.length === 0) {
            return false;
        }
        return (
            this.props.kingdom.unit_queue.filter(function (queue) {
                if (_this.state.unit_to_view !== null) {
                    return queue.game_unit_id === _this.state.unit_to_view.id;
                }
            }).length > 0
        );
    };
    SmallUnitsSection.prototype.render = function () {
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
            this.state.unit_to_view !== null
                ? React.createElement(UnitInformation, {
                      unit: this.state.unit_to_view,
                      close: this.closeSection.bind(this),
                      kingdom_building_cost_reduction:
                          this.props.kingdom.building_cost_reduction,
                      kingdom_iron_cost_reduction:
                          this.props.kingdom.iron_cost_reduction,
                      kingdom_population_cost_reduction:
                          this.props.kingdom.population_cost_reduction,
                      kingdom_current_population:
                          this.props.kingdom.current_population,
                      kingdom_unit_time_reduction:
                          this.props.kingdom.unit_time_reduction,
                      unit_cost_reduction:
                          this.props.kingdom.unit_cost_reduction,
                      character_id: this.props.kingdom.character_id,
                      is_in_queue: this.isUnitInQueue(),
                      kingdom_id: this.props.kingdom.id,
                      buildings: this.props.kingdom.buildings,
                      character_gold: this.props.character_gold,
                  })
                : React.createElement(
                      BasicCard,
                      null,
                      React.createElement(UnitsTable, {
                          units: this.props.kingdom.units,
                          buildings: this.props.kingdom.buildings,
                          dark_tables: this.props.dark_tables,
                          view_unit: this.viewSelectedBuilding.bind(this),
                          units_in_queue: this.props.kingdom.unit_queue,
                          current_units: this.props.kingdom.current_units,
                      }),
                  ),
        );
    };
    return SmallUnitsSection;
})(React.Component);
export default SmallUnitsSection;
//# sourceMappingURL=small-units-section.js.map
