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
import MapSection from "../../../map/map-section";
var SmallMapMovementActions = (function (_super) {
    __extends(SmallMapMovementActions, _super);
    function SmallMapMovementActions(props) {
        return _super.call(this, props) || this;
    }
    SmallMapMovementActions.prototype.render = function () {
        return React.createElement(
            "div",
            { className: "relative" },
            React.createElement(
                "button",
                {
                    type: "button",
                    onClick: this.props.close_map_section,
                    className:
                        "text-red-600 dark:text-red-500 absolute right-[-20px] top-[-20px]",
                },
                React.createElement("i", { className: "fas fa-times-circle" }),
            ),
            React.createElement(MapSection, {
                user_id: this.props.character.user_id,
                character_id: this.props.character.id,
                view_port: this.props.view_port,
                currencies: this.props.character_currencies,
                is_dead: this.props.character.is_dead,
                is_automaton_running:
                    this.props.character.is_automation_running,
                automation_completed_at:
                    this.props.character.automation_completed_at,
                show_celestial_fight_button: this.props.update_celestial,
                set_character_position: this.props.update_character_position,
                update_character_quests_plane: this.props.update_plane_quests,
                disable_bottom_timer: true,
                can_engage_celestial:
                    this.props.character.can_engage_celestials,
                can_engage_celestials_again_at:
                    this.props.character.can_engage_celestials_again_at,
                map_data: this.props.map_data,
                set_map_data: this.props.set_map_data,
            }),
        );
    };
    return SmallMapMovementActions;
})(React.Component);
export default SmallMapMovementActions;
//# sourceMappingURL=small-map-movement-actions.js.map
