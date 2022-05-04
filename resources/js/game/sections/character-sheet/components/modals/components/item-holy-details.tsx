import React, { Fragment } from "react";
import Dialogue from "../../../../../components/ui/dialogue/dialogue";
import {formatNumber} from "../../../../../lib/game/format-number";

export default class ItemHolyDetails extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    renderStacks() {
        return this.props.holy_stacks.map((stack: any, index: number, stacks: any) => {
            return (
                <Fragment>
                    <div className='mb-4'>
                        <h4 className='text-orange-600 dark:text-orange-500 mb-4'>Holy Stack: {index + 1}</h4>
                        <dl>
                            <dt>All Stat Boost %</dt>
                            <dd>{(stack.stat_increase_bonus * 100).toFixed(2)}%</dd>
                            <dt>Devouring Darkness Bonus %</dt>
                            <dd>{(stack.devouring_darkness_bonus * 100).toFixed(2)}%</dd>
                        </dl>
                    </div>
                    {
                        index !== stacks.length - 1 ?
                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                        : null
                    }
                </Fragment>
            );
        });
    }

    render() {
        return(
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.manage_modal}
                      title='Holy Break Down'
                      secondary_actions={null}
            >
                <div className='max-h-[350px] overflow-y-scroll'>
                    {this.renderStacks()}
                </div>
            </Dialogue>
        )
    }
}
