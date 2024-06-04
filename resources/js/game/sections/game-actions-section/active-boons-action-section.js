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
import OrangeButton from "../../components/ui/buttons/orange-button";
import CharacterActiveBoons from "../character-sheet/components/character-active-boons";
import BasicCard from "../../components/ui/cards/basic-card";
import DangerButton from "../../components/ui/buttons/danger-button";
var ActiveBoonsActionSection = (function (_super) {
    __extends(ActiveBoonsActionSection, _super);
    function ActiveBoonsActionSection(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            viewing_active_boons: false,
        };
        return _this;
    }
    ActiveBoonsActionSection.prototype.manageViewingActiveBoons = function () {
        this.setState({
            viewing_active_boons: !this.state.viewing_active_boons,
        });
    };
    ActiveBoonsActionSection.prototype.render = function () {
        return React.createElement(
            React.Fragment,
            null,
            React.createElement(
                "div",
                { className: "mb-4 mt-[-20px] text-center" },
                !this.state.viewing_active_boons
                    ? React.createElement(OrangeButton, {
                          button_label: "Active Boons",
                          on_click: this.manageViewingActiveBoons.bind(this),
                      })
                    : React.createElement(DangerButton, {
                          button_label: "Close Active Boons",
                          on_click: this.manageViewingActiveBoons.bind(this),
                      }),
            ),
            this.state.viewing_active_boons
                ? React.createElement(
                      BasicCard,
                      { additionalClasses: "mb-4" },
                      React.createElement(CharacterActiveBoons, {
                          character_id: this.props.character_id,
                          finished_loading: true,
                      }),
                  )
                : null,
        );
    };
    return ActiveBoonsActionSection;
})(React.Component);
export default ActiveBoonsActionSection;
//# sourceMappingURL=active-boons-action-section.js.map
