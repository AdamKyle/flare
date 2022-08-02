import React, { Fragment } from "react";
import ExplorationSection from "../exploration-section";
import SmallExplorationSectionProps
    from "../../../../lib/game/types/actions/components/smaller-actions/small-exploration-section-props";

export default class SmallExplorationSection extends React.Component<SmallExplorationSectionProps, {}> {

    constructor(props: SmallExplorationSectionProps) {
        super(props);
    }

    render() {
        return(
            <Fragment>
                <button type='button' onClick={this.props.close_exploration_section}
                        className='text-red-600 dark:text-red-500 absolute right-[5px] top-[5px]'
                >
                    <i className="fas fa-times-circle"></i>
                </button>

                <ExplorationSection character={this.props.character}
                                    manage_exploration={this.props.close_exploration_section}
                                    monsters={this.props.monsters} />
            </Fragment>
        )
    }
}
