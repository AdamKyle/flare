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
import PrimaryButton from "../../../../components/ui/buttons/primary-button";
import MonsterActionsManager from "../../../../lib/game/actions/smaller-actions-components/monster-actions-manager";
var Revive = (function (_super) {
    __extends(Revive, _super);
    function Revive(props) {
        var _this = _super.call(this, props) || this;
        _this.monster = new MonsterActionsManager(_this);
        return _this;
    }
    Revive.prototype.revive = function () {
        if (typeof this.props.revive_call_back !== "undefined") {
            this.monster.revive(
                this.props.character_id,
                this.props.revive_call_back,
            );
        } else {
            this.monster.revive(this.props.character_id);
        }
    };
    Revive.prototype.render = function () {
        if (this.props.is_character_dead) {
            return React.createElement(
                "div",
                { className: "text-center my-4 lg:ml-[-140px]" },
                React.createElement(PrimaryButton, {
                    button_label: "Revive",
                    on_click: this.revive.bind(this),
                    additional_css: "mb-4",
                    disabled: !this.props.can_attack,
                }),
                React.createElement("p", null, "You are dead. Please Revive."),
            );
        }
    };
    return Revive;
})(React.Component);
export default Revive;
//# sourceMappingURL=revive.js.map
