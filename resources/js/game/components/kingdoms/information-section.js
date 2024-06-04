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
import BuildingInformation from "./buildings/building-information";
import UnitInformation from "./units/unit-information";
var InformationSection = (function (_super) {
    __extends(InformationSection, _super);
    function InformationSection(props) {
        return _super.call(this, props) || this;
    }
    InformationSection.prototype.render = function () {
        if (this.props.sections.building_to_view !== null) {
            return React.createElement(BuildingInformation, {
                building: this.props.sections.building_to_view,
                close: this.props.close,
                kingdom_building_time_reduction:
                    this.props.cost_reduction.kingdom_building_time_reduction,
                kingdom_building_cost_reduction:
                    this.props.cost_reduction.kingdom_building_cost_reduction,
                kingdom_iron_cost_reduction:
                    this.props.cost_reduction.kingdom_iron_cost_reduction,
                kingdom_population_cost_reduction:
                    this.props.cost_reduction.kingdom_population_cost_reduction,
                kingdom_current_population:
                    this.props.cost_reduction.kingdom_current_population,
                character_id: this.props.character_id,
                user_id: this.props.user_id,
                is_in_queue: this.props.queue.is_building_in_queue,
                character_gold: this.props.character_gold,
            });
        }
        if (this.props.sections.unit_to_view !== null) {
            return React.createElement(UnitInformation, {
                unit: this.props.sections.unit_to_view,
                close: this.props.close,
                kingdom_unit_time_reduction:
                    this.props.cost_reduction.kingdom_unit_time_reduction,
                kingdom_building_cost_reduction:
                    this.props.cost_reduction.kingdom_building_cost_reduction,
                kingdom_iron_cost_reduction:
                    this.props.cost_reduction.kingdom_iron_cost_reduction,
                kingdom_population_cost_reduction:
                    this.props.cost_reduction.kingdom_unit_cost_reduction,
                kingdom_current_population:
                    this.props.cost_reduction.kingdom_current_population,
                unit_cost_reduction:
                    this.props.cost_reduction.kingdom_unit_cost_reduction,
                character_id: this.props.character_id,
                is_in_queue: this.props.queue.is_unit_in_queue,
                kingdom_id: this.props.kingdom_id,
                buildings: this.props.buildings,
                character_gold: this.props.character_gold,
            });
        }
        return null;
    };
    return InformationSection;
})(React.Component);
export default InformationSection;
//# sourceMappingURL=information-section.js.map
