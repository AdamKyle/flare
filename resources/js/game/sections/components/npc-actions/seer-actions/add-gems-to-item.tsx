import React, {Fragment} from "react";
import Select from "react-select";
import AddGemsToItemProps from "./types/add-gems-to-item-props";
import Items from "./deffinitions/items";
import Gems from "./deffinitions/gems";

export default class AddGemsToItem<T> extends React.Component<AddGemsToItemProps<T>, { }> {

    constructor(props: AddGemsToItemProps<T>) {
        super(props);
    }

    createItemsOptions(): {label: string, value: number}[]|[] {

        if (this.props.items.length === 0) {
            return [{
                label: 'Please select item',
                value: 0,
            }];
        }

        const items = this.props.items
            .filter(item => item.socket_amount > 0)
            .map(item => ({
                label: item.name,
                value: item.slot_id,
            }));

        items.unshift({
            label: 'Please select item',
            value: 0,
        });

        return items;
    }

    createGemOptions() {
        const gems = this.props.gems.map((gem: Gems) => {
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
        const item = this.props.items.filter((item: Items) => {
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
        const gem = this.props.gems.filter((gem: Gems) => {
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
