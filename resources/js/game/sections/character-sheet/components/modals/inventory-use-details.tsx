import React, { Fragment } from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import UsableItemSection from "./components/usable-item-section";

export default class InventoryUseDetails extends React.Component<any, any> {
    constructor(props: any) {
        super(props);
    }

    getStatIncrease() {
        switch(this.props.item.holy_level) {
            case 1:
                return '1-3'
            case 2:
                return '1-5'
            case 3:
                return '1-8'
            case 4:
                return '1-10'
            case 5:
                return '1-15'
            default:
                return 'ERROR'
        }
    }

    getDevouringIncrease() {
        switch(this.props.item.holy_level) {
            case 1:
                return '0.001-0.003'
            case 2:
                return '0.001-0.005'
            case 3:
                return '0.001-0.008'
            case 4:
                return '0.001-0.01'
            case 5:
                return '0.001-0.015'
            default:
                return 'ERROR'
        }
    }

    render() {
        return (
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.manage_modal}
                      title={<span className='text-pink-500 dark:text-pink-300'>{this.props.item.item_name}</span>}
            >
                <div className="mb-5">
                    {
                        this.props.item.usable ?
                            <UsableItemSection item={this.props.item} />
                        :
                            <Fragment>
                                <p className='mt-4 mb-4'>
                                    {this.props.item.description}
                                </p>
                                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                                <dl>
                                    <dt>Holy Level</dt>
                                    <dt>{this.props.item.holy_level}</dt>
                                    <dt>Stat Increase Per Item used</dt>
                                    <dd>{this.getStatIncrease()}%</dd>
                                    <dt>Devouring Light Increase Per Item used</dt>
                                    <dd>{this.getDevouringIncrease()}%</dd>
                                </dl>
                                <p className='my-4'>
                                    Read more about Holy Items in the <a href='/information/holy-items' target='_blank'>Help Docs <i
                                    className="fas fa-external-link-alt"></i></a>
                                </p>
                            </Fragment>
                    }

                </div>
            </Dialogue>
        );
    }
}
