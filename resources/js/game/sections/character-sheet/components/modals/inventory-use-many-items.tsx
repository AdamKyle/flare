import React from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import Select from "react-select";
import UsableItemsDetails from "../../../../lib/game/character-sheet/types/inventory/usable-items-details";
import UseManyItems from "../../../../lib/game/character-sheet/ajax/use-many-items";
import LoadingProgressBar from "../../../../components/ui/progress-bars/loading-progress-bar";

export default class InventoryUseManyItems extends React.Component<any, any> {
    constructor(props: any) {
        super(props);

        this.state = {
            loading: false,
            selected_items: [],
            error_message: null,
            using_item: null,
            item_progress: 0,
        }
    }

    useManyItems() {

        this.setState({
            loading: true,
            error_message: null,
        }, () => {
            const items = this.props.items.filter((item: UsableItemsDetails) => this.state.selected_items.includes(item.slot_id)).map((item: UsableItemsDetails) => item.slot_id);

            (new UseManyItems(items, this)).useAllItems(this.props.character_id);
        });
    }

    setItemsToUse(data: any) {
        if (data.length > 10) {
            this.setState({
                error_message: 'You may only apply 10 boons.'
            })
        } else {
            this.setState({
                error_message: null,
                selected_items: data.map((data: any) => data.value),
            })
        }
    }

    buildItems() {
        return this.props.items.filter((item: UsableItemsDetails) => !item.damages_kingdoms && item.usable).map((item: UsableItemsDetails) => {
            return {
                label: item.item_name + ' Lasts for: ' + item.lasts_for + ' minutes',
                value: item.slot_id,
            }
        })
    }

    defaultItem() {
        if (this.state.selected_items.length === 0) {
            return [];
        }

        return this.props.items.filter((item: UsableItemsDetails) => this.state.selected_items.includes(item.slot_id)).map((item: UsableItemsDetails) => {
            return {
                label: item.item_name + ' Lasts for: ' + item.lasts_for + ' minutes',
                value: item.slot_id,
            }
        })
    }

    render() {
        return (
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.manage_modal}
                      title={'Use many items'}
                      primary_button_disabled={this.state.loading}
                      secondary_actions={{
                          secondary_button_disabled: this.state.loading,
                          secondary_button_label: 'Use selected',
                          handle_action: () => this.useManyItems()
                      }}
            >
                <div className="mb-5">
                    <p className='mt-4 mb-4 text-sky-700 dark:text-sky-500'>
                        You may select up to 10 boons to apply to your self. Only usable items will be listed below.
                    </p>
                    <p className='mb-4'>
                        Boons stack on to of each other, making applying multiple beneficial. When a character switches to a plane like Shadow Plane, Hell or Purgatory,
                        we recalculate your stats based on plane stat reductions based off your surface level stats. The more boons you use,
                        the more stats you have for harder planes of existence.
                    </p>
                    <Select
                        onChange={this.setItemsToUse.bind(this)}
                        options={this.buildItems()}
                        menuPosition={'absolute'}
                        menuPlacement={'bottom'}
                        isMulti
                        styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                        menuPortalTarget={document.body}
                        value={this.defaultItem()}
                    />
                    {
                        this.state.loading ?
                            <div className='mt-4 mb-4'>
                                <LoadingProgressBar />
                            </div>
                        : null
                    }
                </div>
            </Dialogue>
        );
    }
}
