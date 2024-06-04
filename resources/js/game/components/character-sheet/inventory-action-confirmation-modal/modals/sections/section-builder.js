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
import { InventoryActionConfirmationType } from "../../helpers/enums/inventory-action-confirmation-type";
import DestroyInformation from "./destroy-information";
import DestroySelectedInformation from "./destroy-selected-information";
import SellInformation from "./sell-information";
import SellSelectedInformation from "./sell-selected-information";
import DisenchantInformation from "./disenchant-information";
import DisenchantSelectedInformation from "./disenchant-selected-information";
import EquipSelectedInformation from "./equip-selected-information";
import MoveSelectedInformation from "./move-selected-information";
import DestroyAlchemyInformation from "./destroy-alchemy-information";
var SectionBuilder = (function (_super) {
    __extends(SectionBuilder, _super);
    function SectionBuilder(props) {
        return _super.call(this, props) || this;
    }
    SectionBuilder.prototype.render = function () {
        switch (this.props.type) {
            case InventoryActionConfirmationType.DESTROY_ALL:
                return React.createElement(DestroyInformation, null);
            case InventoryActionConfirmationType.DESTROY_SELECTED:
                return React.createElement(DestroySelectedInformation, {
                    item_names: this.props.item_names
                        ? this.props.item_names
                        : [],
                });
            case InventoryActionConfirmationType.SELL_ALL:
                return React.createElement(SellInformation, null);
            case InventoryActionConfirmationType.SELL_SELECTED:
                return React.createElement(SellSelectedInformation, {
                    item_names: this.props.item_names
                        ? this.props.item_names
                        : [],
                });
            case InventoryActionConfirmationType.DISENCHANT_ALL:
                return React.createElement(DisenchantInformation, null);
            case InventoryActionConfirmationType.DISENCHANT_SELECTED:
                return React.createElement(DisenchantSelectedInformation, {
                    item_names: this.props.item_names
                        ? this.props.item_names
                        : [],
                });
            case InventoryActionConfirmationType.EQUIP_SELECTED:
                return React.createElement(EquipSelectedInformation, {
                    item_names: this.props.item_names
                        ? this.props.item_names
                        : [],
                });
            case InventoryActionConfirmationType.MOVE_SELECTED:
                return React.createElement(MoveSelectedInformation, {
                    item_names: this.props.item_names
                        ? this.props.item_names
                        : [],
                    usable_sets: this.props.usable_sets,
                    update_api_params: this.props.update_api_params,
                });
            case InventoryActionConfirmationType.DESTROY_ALL_ALCHEMY_ITEMS:
                return React.createElement(DestroyAlchemyInformation, null);
            default:
                return null;
        }
    };
    return SectionBuilder;
})(React.Component);
export default SectionBuilder;
//# sourceMappingURL=section-builder.js.map
