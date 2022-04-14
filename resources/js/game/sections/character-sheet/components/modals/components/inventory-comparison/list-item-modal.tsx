import React from "react";
import Dialogue from "../../../../../../components/ui/dialogue/dialogue";
import ItemNameColorationText from "../../../../../../components/ui/item-name-coloration-text";
import {LineChart} from "../../../../../../components/ui/charts/line-chart";

export default class ListItemModal extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }


    render() {
        return (
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.manage_modal}
                      title={'List item on market'}
                      secondary_actions={null}
            >
                <h3 className='mb-4 mt-4'><ItemNameColorationText item={{...this.props.item, ['name']: this.props.item.affix_name}} /></h3>
                <LineChart />
            </Dialogue>
        );
    }
}
