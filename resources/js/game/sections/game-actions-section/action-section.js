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
import SmallerActions from "./smaller-actions";
import Actions from "./actions";
var ActionSection = (function (_super) {
    __extends(ActionSection, _super);
    function ActionSection(props) {
        return _super.call(this, props) || this;
    }
    ActionSection.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            this.props.view_port < 1600
                ? React.createElement(SmallerActions, {
                      character: this.props.character,
                      character_status: this.props.character_status,
                      character_position: this.props.character_position,
                      character_currencies: this.props.character_currencies,
                      celestial_id: this.props.celestial_id,
                      update_celestial: this.props.update_celestial,
                      update_plane_quests: this.props.update_plane_quests,
                      update_character_position:
                          this.props.update_character_position,
                      view_port: this.props.view_port,
                      can_engage_celestial:
                          this.props.character.can_engage_celestials,
                      action_data: this.props.action_data,
                      map_data: this.props.map_data,
                      update_parent_state: this.props.update_parent_state,
                      set_map_data: this.props.set_map_data,
                      fame_tasks: this.props.fame_tasks,
                  })
                : React.createElement(Actions, {
                      character: this.props.character,
                      character_status: this.props.character_status,
                      character_position: this.props.character_position,
                      celestial_id: this.props.celestial_id,
                      update_celestial: this.props.update_celestial,
                      can_engage_celestial:
                          this.props.character.can_engage_celestials,
                      action_data: this.props.action_data,
                      update_parent_state: this.props.update_parent_state,
                      fame_tasks: this.props.fame_tasks,
                  }),
        );
    };
    return ActionSection;
})(React.Component);
export default ActionSection;
//# sourceMappingURL=action-section.js.map
