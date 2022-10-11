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
                <ExplorationSection character={this.props.character}
                                    manage_exploration={this.props.close_exploration_section}
                                    monsters={this.props.monsters} />
            </Fragment>
        )
    }
}
