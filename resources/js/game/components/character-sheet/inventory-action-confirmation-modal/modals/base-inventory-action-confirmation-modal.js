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
import InventoryActionConfirmationModal from "../../../../sections/character-sheet/components/modals/inventory-action-confirmation-modal";
import SectionBuilder from "./sections/section-builder";
import { InventoryActionConfirmationType } from "../helpers/enums/inventory-action-confirmation-type";
var BaseInventoryActionConfirmationModal = (function (_super) {
    __extends(BaseInventoryActionConfirmationModal, _super);
    function BaseInventoryActionConfirmationModal(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            params: props.data.params,
        };
        return _this;
    }
    BaseInventoryActionConfirmationModal.prototype.updateParams = function (
        params,
    ) {
        this.setState({
            params: __assign(__assign({}, this.state.params), params),
        });
    };
    BaseInventoryActionConfirmationModal.prototype.render = function () {
        return React.createElement(
            InventoryActionConfirmationModal,
            {
                is_open: this.props.is_open,
                manage_modal: this.props.manage_modal,
                title: this.props.title,
                url: this.props.data.url,
                ajax_params: this.state.params,
                update_inventory: this.props.update_inventory,
                set_success_message: this.props.set_success_message,
                is_large_modal:
                    this.props.type ===
                    InventoryActionConfirmationType.MOVE_SELECTED,
            },
            React.createElement(SectionBuilder, {
                type: this.props.type,
                item_names: this.props.selected_item_names,
                usable_sets: this.props.usable_sets,
                update_api_params: this.updateParams.bind(this),
            }),
        );
    };
    return BaseInventoryActionConfirmationModal;
})(React.Component);
export default BaseInventoryActionConfirmationModal;
//# sourceMappingURL=base-inventory-action-confirmation-modal.js.map
