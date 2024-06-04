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
import PrimaryButton from "../../../ui/buttons/primary-button";
import DangerButton from "../../../ui/buttons/danger-button";
import SuccessButton from "../../../ui/buttons/success-button";
import OrangeButton from "../../../ui/buttons/orange-button";
var CraftingActionButtons = (function (_super) {
    __extends(CraftingActionButtons, _super);
    function CraftingActionButtons(props) {
        return _super.call(this, props) || this;
    }
    CraftingActionButtons.prototype.render = function () {
        var _this = this;
        return React.createElement(
            Fragment,
            null,
            React.createElement(PrimaryButton, {
                additional_css: "mb-2",
                button_label: "Craft",
                on_click: function () {
                    return _this.props.craft(false, false);
                },
                disabled: this.props.can_craft,
            }),
            this.props.show_craft_for_npc
                ? React.createElement(SuccessButton, {
                      additional_css: "lg:ml-2 mb-2",
                      button_label: "Craft for NPC",
                      on_click: function () {
                          return _this.props.craft(true, false);
                      },
                      disabled: this.props.can_craft,
                  })
                : null,
            this.props.show_craft_for_event
                ? React.createElement(OrangeButton, {
                      additional_css: "lg:ml-2 mb-2",
                      button_label: "Craft for Event",
                      on_click: function () {
                          return _this.props.craft(false, true);
                      },
                      disabled: this.props.can_craft,
                  })
                : null,
            React.createElement(PrimaryButton, {
                button_label: "Change Type",
                on_click: this.props.change_type,
                disabled: this.props.can_change_type,
                additional_css: "lg:ml-2 mb-2",
            }),
            React.createElement(DangerButton, {
                button_label: "Close",
                on_click: this.props.clear_crafting,
                additional_css: "lg:ml-2",
                disabled: this.props.can_close,
            }),
            React.createElement(
                "a",
                {
                    href: "/information/crafting",
                    target: "_blank",
                    className: "relative top-[20px] md:top-[0px] ml-2",
                },
                "Help ",
                React.createElement("i", {
                    className: "fas fa-external-link-alt",
                }),
            ),
        );
    };
    return CraftingActionButtons;
})(React.Component);
export default CraftingActionButtons;
//# sourceMappingURL=crafting-action-buttons.js.map
