import React from "react";
import Select from "react-select";
import PopOverContainer from "../../../components/ui/popover/pop-over-container";
import PrimaryButton from "../../../components/ui/buttons/primary-button";

export default class CraftingSection extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            selected_item: null,
        }

    }

    setItemToCraft(data: any) {

    }


    buildItems() {
        return [{label: 'item - cost', value: 0}];
    }

    defaultItem() {

        if (this.state.selected_item !== null) {
            return {
                label: this.state.selected_item.name,
                value: this.state.selected_item.id,
            }
        }

        return {label: 'item - cost', value: 0};
    }

    craft() {

    }

    render() {
        return (
            <div className='mt-2 grid grid-cols-3 gap-2'>
                <div className='cols-start-1 col-span-2'>
                    <Select
                        onChange={this.setItemToCraft.bind(this)}
                        options={this.buildItems()}
                        menuPosition={'absolute'}
                        menuPlacement={'bottom'}
                        styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                        menuPortalTarget={document.body}
                        value={this.defaultItem()}
                    />
                </div>
                <div className='cols-start-3 cols-end-3'>
                    <PrimaryButton button_label={'Craft'} on_click={this.craft.bind(this)} />
                </div>
            </div>
        )
    }

}
