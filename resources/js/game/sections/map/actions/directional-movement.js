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
import MovePlayer from "../lib/ajax/move-player";
import PrimaryOutlineButton from "../../../components/ui/buttons/primary-outline-button";
import TraverseModal from "../modals/traverse-modal";
var DirectionalMovement = (function (_super) {
    __extends(DirectionalMovement, _super);
    function DirectionalMovement(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            show_traverse: false,
        };
        return _this;
    }
    DirectionalMovement.prototype.move = function (direction) {
        this.handleMovePlayer(direction);
    };
    DirectionalMovement.prototype.traverse = function () {
        this.setState({
            show_traverse: !this.state.show_traverse,
        });
    };
    DirectionalMovement.prototype.handleMovePlayer = function (direction) {
        new MovePlayer(this)
            .setCharacterPosition(this.props.character_position)
            .setMapPosition(this.props.map_position)
            .movePlayer(this.props.character_id, direction, this);
    };
    DirectionalMovement.prototype.render = function () {
        var _this = this;
        return React.createElement(
            Fragment,
            null,
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 hidden sm:block",
            }),
            React.createElement(
                "div",
                { className: "grid gap-2 md:grid-cols-5 gap-4" },
                React.createElement(PrimaryOutlineButton, {
                    disabled: !this.props.can_move || this.props.is_dead,
                    button_label: "North",
                    on_click: function () {
                        return _this.move("north");
                    },
                }),
                React.createElement(PrimaryOutlineButton, {
                    disabled: !this.props.can_move || this.props.is_dead,
                    button_label: "South",
                    on_click: function () {
                        return _this.move("south");
                    },
                }),
                React.createElement(PrimaryOutlineButton, {
                    disabled: !this.props.can_move || this.props.is_dead,
                    button_label: "West",
                    on_click: function () {
                        return _this.move("west");
                    },
                }),
                React.createElement(PrimaryOutlineButton, {
                    disabled: !this.props.can_move || this.props.is_dead,
                    button_label: "East",
                    on_click: function () {
                        return _this.move("east");
                    },
                }),
                React.createElement(PrimaryOutlineButton, {
                    disabled:
                        !this.props.can_move ||
                        this.props.is_dead ||
                        this.props.is_automation_running,
                    button_label: "Traverse",
                    on_click: function () {
                        return _this.traverse();
                    },
                }),
            ),
            this.state.show_traverse
                ? React.createElement(TraverseModal, {
                      is_open: true,
                      handle_close: this.traverse.bind(this),
                      character_id: this.props.character_id,
                      map_id: this.props.map_id,
                  })
                : null,
        );
    };
    return DirectionalMovement;
})(React.Component);
export default DirectionalMovement;
//# sourceMappingURL=directional-movement.js.map
