import React from "react";
import ItemViewProps from "./types/item-view-props";
import {ItemType} from "../../items/enums/item-type";
import AlchemyItemHoly from "./item-views/alchemy-item-holy";
import AlchemyItemUsable from "./item-views/alchemy-item-usable";
import QuestItem from "./item-views/quest-item";
import GemDetails from "./item-views/gem-details";
import ItemComparison from "../../item-comparison/item-comparison";
import ItemViewState from "./types/item-view-state";
import PrimaryOutlineButton from "../../ui/buttons/primary-outline-button";

export default class ItemView extends React.Component<ItemViewProps, ItemViewState> {

    constructor(props: ItemViewProps) {
        super(props);

        this.state = {
            is_showing_expanded_comparison: false,
        }
    }

    updateIsShowingExpandedLocation() {
        this.setState({
            is_showing_expanded_comparison: !this.state.is_showing_expanded_comparison,
        });
    }

    render() {

        const item = this.props.comparison_details.itemToEquip;

        if (item.type === ItemType.ALCHEMY) {

            if (item.holy_level !== null) {
                return <AlchemyItemHoly item={item} />
            }

            return <AlchemyItemUsable item={item} />
        }

        if (item.type === ItemType.QUEST) {
            return <QuestItem item={item} />
        }

        if (item.type === ItemType.GEM) {
            return <GemDetails gem={item.item.gem} />
        }

        return (
            <>
                {
                    this.state.is_showing_expanded_comparison ?
                        <PrimaryOutlineButton button_label={
                            'Back to comparison'
                        } on_click={this.updateIsShowingExpandedLocation.bind(this)} additional_css={'my-4'} />
                    : null
                }

                <ItemComparison comparison_info={this.props.comparison_details}
                                is_showing_expanded_comparison={this.state.is_showing_expanded_comparison}
                                manage_show_expanded_comparison={this.updateIsShowingExpandedLocation.bind(this)}
                                mobile_height_restriction={true}
                />
            </>

        )
    }
}
