import React, {Fragment} from "react";
import Select from "react-select";
import ManageItemSocketsProps from "./types/manage-item-sockets-props";
import ManageItemSocketsState from "./types/manage-item-sockets-state";
import Items from "./deffinitions/items";

export default class ManageItemSockets<T> extends React.Component<ManageItemSocketsProps<T>, ManageItemSocketsState> {

    constructor(props: ManageItemSocketsProps<T>) {
        super(props);

        this.state = {
            selected_item_slot_id: 0,
        }
    }

    setSelectedItem(data: any) {
        this.setState({
            selected_item_slot_id: data.value,
        }, () => {
            this.props.update_parent(data.value, 'item_selected');
        })
    }

    itemOptions(): {label: string, value: number}[] {
        let options = this.props.items.map((item: Items) => {
            return {
                label: item.name,
                value: item.slot_id,
            }
        });

        options.unshift({
            label: 'Please select an item',
            value: 0,
        });

        return options;
    }

    selectedItem(): {label: string, value: number} {
        let item = this.props.items.filter((item: Items) => {
            return item.slot_id === this.state.selected_item_slot_id;
        });

        if (item.length > 0) {
            return {
                label: item[0].name,
                value: item[0].slot_id,
            }
        }

        return {
            label: 'Please select an an item',
            value: 0,
        }

    }

    render() {
        return (
            <Fragment>
                <Select
                    onChange={this.setSelectedItem.bind(this)}
                    options={this.itemOptions()}
                    menuPosition={'absolute'}
                    menuPlacement={'bottom'}
                    styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                    menuPortalTarget={document.body}
                    value={this.selectedItem()}
                />
            </Fragment>
        )
    }
}
