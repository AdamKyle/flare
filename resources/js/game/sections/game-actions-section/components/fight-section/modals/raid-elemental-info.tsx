
import React from "react";
import Dialogue from "../../../../../components/ui/dialogue/dialogue";
import { startCase } from "lodash";

export default class RaidElementInfo extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    rnderHighestElementCheck(key: string): JSX.Element | null {
        if (this.props.highest_element === key) {
            return <i className="fas fa-check text-green-600 dark:text-green-400"></i>
        }

        return null;
    }

    renderAtonementData() {
        const dlElements = [];

        for (const key in this.props.element_atonements) {
            const value = this.props.element_atonements[key];

            dlElements.push(
                <>
                    <dd>{startCase(key)} {this.rnderHighestElementCheck(key)}:</dd>
                    <dt>{(value * 100).toFixed(0)}%</dt>
                </>
            )
        }

        return dlElements;
    }

    render() {
        return (
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.manage_modal}
                      title={this.props.monster_name + ' Elemental Atonement'}
            >
                <p className='my-4'>
                    Below you will find elemental atonement info about the monster in question. Matching your elemental atonement through
                    the use of <a href='/information/gems' target='_blank'>Gems <i className="fas fa-external-link-alt"></i></a>.
                </p>
                <p className='my-4'>
                    When an enemy attacks, they will do a % of their weapon damage as that elements damage towards you. For example if there attack is 500, and
                    the enemies highest element is 15% in water they will do 15% of 500 towards you as water damage. If your element is Fire, they will do
                    double that damage. If your element is Ice, they will do half damage to you.
                </p>
                <p className='my-4'>
                    The green Checkmark beside the element name, means this is the core attacking element and you will want the oppisite element to do
                    the most damage. For example if the enemy is Fire based, you want Water. If the element is Water you want Ice.
                </p>
                <dl className='my-4'>
                    {this.renderAtonementData()}
                </dl>
            </Dialogue>
        )
    }
}
