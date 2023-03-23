import React, {Fragment} from "react";
import Select from "react-select";

export default class ManageItemSockets extends React.Component<any, any> {

    constructor(props: any) {
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

    itemOptions() {
        let options = this.props.items.map((item: any) => {
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

    selectedItem() {
        let item = this.props.items.filter((item: any) => {
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
