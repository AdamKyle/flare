import React, {Fragment} from "react";
import ItemsForSeer from "../../../../../../lib/game/types/actions/components/seer-camp/items-for-seer";
import GemsForSeer from "../../../../../../lib/game/types/actions/components/seer-camp/gems-for-seer";
import Select from "react-select";

export default class AddGemsToItem extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    createItemsOptions() {
        const items = this.props.items.map((item: ItemsForSeer) => {
            if (item.socket_amount > 0) {
                return {
                    label: item.name,
                    value: item.slot_id,
                }
            }
        }).filter((item: { label: string, value: number } | undefined) => {
            return typeof item !== 'undefined';
        });

        items.unshift({
            label: 'Please select item',
            value: 0,
        });

        return items;
    }

    createGemOptions() {
        const gems = this.props.gems.map((gem: GemsForSeer) => {
            return {
                label: gem.name + ' (Amount: '+gem.amount+', Tier: '+gem.tier+')',
                value: gem.slot_id
            }
        });

        gems.unshift({
            label: 'Please select gem',
            value: 0,
        });

        return gems;
    }

    setItemToUse(data: any) {
        this.props.update_parent(data.value, 'item_selected');
    }

    setGemToUse(data: any) {
        this.props.update_parent(data.value, 'gem_selected');
    }

    defaultValueForItems() {
        const item = this.props.items.filter((item: ItemsForSeer) => {
            return item.slot_id === this.props.item_selected
        });

        if (item.length > 0) {
            return {label: item[0].name, value: item[0].slot_id};
        }

        return {
            label: 'Please select item',
            value: 0,
        }
    }

    defaultValueForGems() {
        const gem = this.props.gems.filter((gem: GemsForSeer) => {
            return gem.slot_id === this.props.gem_selected
        });

        if (gem.length > 0) {
            return {label: gem[0].name + ' (Amount: '+gem[0].amount+', Tier: '+gem[0].tier+')', value: gem[0].slot_id};
        }

        return {
            label: 'Please select gem',
            value: 0,
        }
    }

    render() {
        return (
            <Fragment>
                <div className='mb-3'>
                <Select
                    onChange={this.setItemToUse.bind(this)}
                    options={this.createItemsOptions()}
                    menuPosition={'absolute'}
                    menuPlacement={'bottom'}
                    styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                    menuPortalTarget={document.body}
                    value={this.defaultValueForItems()}
                />
                </div>
                <Select
                    onChange={this.setGemToUse.bind(this)}
                    options={this.createGemOptions()}
                    menuPosition={'absolute'}
                    menuPlacement={'bottom'}
                    styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                    menuPortalTarget={document.body}
                    value={this.defaultValueForGems()}
                />
            </Fragment>
        )
    }
}
