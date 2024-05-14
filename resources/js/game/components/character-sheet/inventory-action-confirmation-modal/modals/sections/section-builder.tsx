import React from "react";
import { InventoryActionConfirmationType } from "../../helpers/enums/inventory-action-confirmation-type";
import DestroyInformation from "./destroy-information";
import SectionBuilderProps from "../../types/modals/sections/section-builder-props";
import DestroySelectedInformation from "./destroy-selected-information";
import SellInformation from "./sell-information";
import SellSelectedInformation from "./sell-selected-information";
import DisenchantInformation from "./disenchant-information";
import DisenchantSelectedInformation from "./disenchant-selected-information";
import EquipSelectedInformation from "./equip-selected-information";
import MoveSelectedInformation from "./move-selected-information";

export default class SectionBuilder extends React.Component<
    SectionBuilderProps,
    {}
> {
    constructor(props: SectionBuilderProps) {
        super(props);
    }

    render() {
        switch (this.props.type) {
            case InventoryActionConfirmationType.DESTROY_ALL:
                return <DestroyInformation />;
            case InventoryActionConfirmationType.DESTROY_SELECTED:
                return (
                    <DestroySelectedInformation
                        item_names={
                            this.props.item_names ? this.props.item_names : []
                        }
                    />
                );
            case InventoryActionConfirmationType.SELL_ALL:
                return <SellInformation />;
            case InventoryActionConfirmationType.SELL_SELECTED:
                return (
                    <SellSelectedInformation
                        item_names={
                            this.props.item_names ? this.props.item_names : []
                        }
                    />
                );
            case InventoryActionConfirmationType.DISENCHANT_ALL:
                return <DisenchantInformation />;
            case InventoryActionConfirmationType.DISENCHANT_SELECTED:
                return (
                    <DisenchantSelectedInformation
                        item_names={
                            this.props.item_names ? this.props.item_names : []
                        }
                    />
                );
            case InventoryActionConfirmationType.EQUIP_SELECTED:
                return (
                    <EquipSelectedInformation
                        item_names={
                            this.props.item_names ? this.props.item_names : []
                        }
                    />
                );
            case InventoryActionConfirmationType.MOVE_SELECTED:
                return (
                    <MoveSelectedInformation
                        item_names={
                            this.props.item_names ? this.props.item_names : []
                        }
                        usable_sets={this.props.usable_sets}
                        update_api_params={this.props.update_api_params}
                    />
                );
            default:
                return null;
        }
    }
}
