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
import Dialogue from "../../../ui/dialogue/dialogue";
import { EquipPositions } from "../../../../lib/game/character-sheet/helpers/inventory/equip-positions";
import PrimaryButton from "../../../ui/buttons/primary-button";
import { capitalize } from "lodash";
var EquipModal = (function (_super) {
    __extends(EquipModal, _super);
    function EquipModal(props) {
        return _super.call(this, props) || this;
    }
    EquipModal.prototype.equip = function (type, position) {
        this.props.equip_item(type, position);
        this.props.manage_modal();
    };
    EquipModal.prototype.renderEquipButtons = function () {
        var _this = this;
        var buttons = EquipPositions.getAllowedPositions(
            this.props.item_to_equip.type,
        );
        if (buttons === null) {
            return React.createElement(PrimaryButton, {
                button_label: "Equip",
                on_click: function () {
                    return _this.equip(
                        _this.props.item_to_equip.type,
                        _this.props.item_to_equip.type,
                    );
                },
            });
        }
        var buttonArray = buttons.map(function (button) {
            return React.createElement(PrimaryButton, {
                button_label: capitalize(button.split("-").join(" ")),
                on_click: function () {
                    return _this.equip(_this.props.item_to_equip.type, button);
                },
            });
        });
        return React.createElement(
            "div",
            { className: "grid grid-cols-2 gap-2" },
            buttonArray,
        );
    };
    EquipModal.prototype.replacesTwoHandedItem = function () {
        if (
            this.props.is_bow_equipped ||
            this.props.is_hammer_equipped ||
            this.props.is_stave_equipped
        ) {
            return ["hammer", "stave", "bow"].includes(
                this.props.item_to_equip.type,
            );
        }
        return false;
    };
    EquipModal.prototype.render = function () {
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.manage_modal,
                title: "Select Position",
            },
            EquipPositions.isTwoHanded(this.props.item_to_equip.type)
                ? React.createElement(
                      "p",
                      { className: "mt-3 mb-3" },
                      "It doesn't matter which hand you select for this item, as both hands will be used.",
                  )
                : null,
            EquipPositions.isArmour(this.props.item_to_equip.type)
                ? React.createElement(
                      "p",
                      { className: "mt-3 mb-3" },
                      "This item has a default position already selected. (Armour will never let you select the position)",
                  )
                : null,
            EquipPositions.isArtifact(this.props.item_to_equip.type)
                ? React.createElement(
                      "p",
                      { className: "mt-3 mb-3" },
                      "This item has a default position already selected. (Ancestral [artifacts] will never let you select the position)",
                  )
                : null,
            this.replacesTwoHandedItem()
                ? React.createElement(
                      "p",
                      { className: "mt-3 mb-3" },
                      "Equipping this item will remove your two handed equipped item.",
                  )
                : null,
            EquipPositions.isTrinket(this.props.item_to_equip.type)
                ? React.createElement(
                      "p",
                      { className: "mt-3 mb-3" },
                      "Trinkets have their own dedicated slot. Players may only equip one trinket",
                  )
                : null,
            this.renderEquipButtons(),
        );
    };
    return EquipModal;
})(React.Component);
export default EquipModal;
//# sourceMappingURL=equip-modal.js.map
