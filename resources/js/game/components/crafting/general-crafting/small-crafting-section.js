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
var __assign =
    (this && this.__assign) ||
    function () {
        __assign =
            Object.assign ||
            function (t) {
                for (var s, i = 1, n = arguments.length; i < n; i++) {
                    s = arguments[i];
                    for (var p in s)
                        if (Object.prototype.hasOwnProperty.call(s, p))
                            t[p] = s[p];
                }
                return t;
            };
        return __assign.apply(this, arguments);
    };
import React from "react";
import CraftingSectionManager from "../../../lib/game/actions/smaller-actions-components/crafting-section-manager";
import MainCraftingSection from "../base-components/crafting-section";
import DangerButton from "../../ui/buttons/danger-button";
import Select from "react-select";
var SmallCraftingSection = (function (_super) {
    __extends(SmallCraftingSection, _super);
    function SmallCraftingSection(props) {
        var _this = _super.call(this, props) || this;
        _this.craftingSectionManager = new CraftingSectionManager(_this);
        _this.state = {
            crafting_type: null,
        };
        return _this;
    }
    SmallCraftingSection.prototype.removeCraftingType = function () {
        this.setState({
            crafting_type: null,
        });
    };
    SmallCraftingSection.prototype.setCraftingType = function (type) {
        this.setState({
            crafting_type: type,
        });
    };
    SmallCraftingSection.prototype.render = function () {
        var _this = this;
        return React.createElement(
            "div",
            { className: "relative" },
            this.state.crafting_type !== null
                ? React.createElement(MainCraftingSection, {
                      remove_crafting: this.removeCraftingType.bind(this),
                      type: this.state.crafting_type,
                      character_id: this.props.character.id,
                      user_id: this.props.character.user_id,
                      cannot_craft: this.craftingSectionManager.cannotCraft(),
                      fame_tasks: this.props.fame_tasks,
                      is_small: true,
                  })
                : React.createElement(
                      "div",
                      { className: "text-center my-4" },
                      React.createElement(Select, {
                          onChange: function (value) {
                              return _this.craftingSectionManager.setCraftingTypeForSmallerActionsList(
                                  value,
                              );
                          },
                          options:
                              this.craftingSectionManager.smallCraftingList(),
                          menuPosition: "absolute",
                          menuPlacement: "bottom",
                          styles: {
                              menuPortal: function (base) {
                                  return __assign(__assign({}, base), {
                                      zIndex: 9999,
                                      color: "#000000",
                                  });
                              },
                          },
                          menuPortalTarget: document.body,
                          value: this.craftingSectionManager.getSelectedCraftingTypeForSmallerActionsList(),
                      }),
                      React.createElement(DangerButton, {
                          button_label: "Close",
                          on_click: this.props.close_crafting_section,
                          additional_css: "my-4 w-full",
                      }),
                  ),
        );
    };
    return SmallCraftingSection;
})(React.Component);
export default SmallCraftingSection;
//# sourceMappingURL=small-crafting-section.js.map
