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
import { ItemType } from "../../items/enums/item-type";
import AlchemyItemHoly from "./item-views/alchemy-item-holy";
import AlchemyItemUsable from "./item-views/alchemy-item-usable";
import QuestItem from "./item-views/quest-item";
import GemDetails from "./item-views/gem-details";
import ItemComparison from "../../item-comparison/item-comparison";
import { watchForChatDarkModeItemViewChange } from "../../../lib/game/dark-mode-watcher";
import ItemActions from "./item-actions";
var ItemView = (function (_super) {
    __extends(ItemView, _super);
    function ItemView(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            dark_charts: false,
        };
        return _this;
    }
    ItemView.prototype.componentDidMount = function () {
        watchForChatDarkModeItemViewChange(this);
    };
    ItemView.prototype.render = function () {
        var item = this.props.comparison_details.itemToEquip;
        if (item.type === ItemType.ALCHEMY) {
            if (item.holy_level !== null) {
                return React.createElement(AlchemyItemHoly, { item: item });
            }
            return React.createElement(AlchemyItemUsable, { item: item });
        }
        if (item.type === ItemType.QUEST) {
            return React.createElement(QuestItem, { item: item });
        }
        if (item.type === ItemType.GEM) {
            return React.createElement(GemDetails, { gem: item.item.gem });
        }
        return React.createElement(
            React.Fragment,
            null,
            React.createElement(ItemComparison, {
                comparison_info: this.props.comparison_details,
                is_showing_expanded_comparison:
                    this.props.is_showing_expanded_section,
                manage_show_expanded_comparison:
                    this.props.manage_showing_expanded_section,
                mobile_height_restriction: true,
            }),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
            }),
            React.createElement(ItemActions, {
                slot_id: this.props.comparison_details.slotId,
                character_id: this.props.comparison_details.characterId,
                dark_charts: this.state.dark_charts,
                is_automation_running: this.props.is_automation_running,
                is_dead: this.props.is_dead,
                comparison_details: this.props.comparison_details,
                usable_sets: this.props.usable_sets,
                manage_modal: this.props.manage_modal,
                update_inventory: this.props.update_inventory,
                set_success_message: this.props.set_success_message,
            }),
        );
    };
    return ItemView;
})(React.Component);
export default ItemView;
//# sourceMappingURL=item-view.js.map
