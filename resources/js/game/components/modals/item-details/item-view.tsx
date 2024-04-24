import React from "react";
import ItemViewProps from "./types/item-view-props";
import { ItemType } from "../../items/enums/item-type";
import AlchemyItemHoly from "./item-views/alchemy-item-holy";
import AlchemyItemUsable from "./item-views/alchemy-item-usable";
import QuestItem from "./item-views/quest-item";
import GemDetails from "./item-views/gem-details";
import ItemComparison from "../../item-comparison/item-comparison";
import ItemViewState from "./types/item-view-state";
import PrimaryOutlineButton from "../../ui/buttons/primary-outline-button";
import { watchForChatDarkModeItemViewChange } from "../../../lib/game/dark-mode-watcher";
import ItemActions from "./item-actions";

export default class ItemView extends React.Component<
    ItemViewProps,
    ItemViewState
> {
    constructor(props: ItemViewProps) {
        super(props);

        this.state = {
            dark_charts: false,
        };
    }

    componentDidMount() {
        watchForChatDarkModeItemViewChange(this);
    }

    render() {
        const item = this.props.comparison_details.itemToEquip;

        if (item.type === ItemType.ALCHEMY) {
            if (item.holy_level !== null) {
                return <AlchemyItemHoly item={item} />;
            }

            return <AlchemyItemUsable item={item} />;
        }

        if (item.type === ItemType.QUEST) {
            return <QuestItem item={item} />;
        }

        if (item.type === ItemType.GEM) {
            return <GemDetails gem={item.item.gem} />;
        }

        return (
            <>
                <ItemComparison
                    comparison_info={this.props.comparison_details}
                    is_showing_expanded_comparison={
                        this.props.is_showing_expanded_section
                    }
                    manage_show_expanded_comparison={
                        this.props.manage_showing_expanded_section
                    }
                    mobile_height_restriction={true}
                />

                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>

                <ItemActions
                    slot_id={this.props.comparison_details.slotId}
                    character_id={this.props.comparison_details.characterId}
                    dark_charts={this.state.dark_charts}
                    is_automation_running={this.props.is_automation_running}
                    is_dead={this.props.is_dead}
                    comparison_details={this.props.comparison_details}
                    usable_sets={this.props.usable_sets}
                    manage_modal={this.props.manage_modal}
                    update_inventory={this.props.update_inventory}
                    set_success_message={this.props.set_success_message}
                />
            </>
        );
    }
}
