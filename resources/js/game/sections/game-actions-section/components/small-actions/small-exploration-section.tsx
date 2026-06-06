import React, { Fragment } from "react";
import ExplorationSection from "../exploration-section";
import ExplorationOutputSection from "../exploration-output-section";
import SmallExplorationSectionProps from "./types/small-exploration-section-props";

export default class SmallExplorationSection extends React.Component<
    SmallExplorationSectionProps,
    {}
> {
    constructor(props: SmallExplorationSectionProps) {
        super(props);
    }

    render() {
        return (
            <Fragment>
                <ExplorationSection
                    character={this.props.character}
                    manage_exploration={this.props.close_exploration_section}
                    monsters={this.props.monsters}
                    exploration_output={
                        (
                            this.props as SmallExplorationSectionProps & {
                                exploration_output: any;
                            }
                        ).exploration_output
                    }
                />
                {!this.props.character.is_automation_running ? (
                    <ExplorationOutputSection
                        character_id={this.props.character.id}
                        exploration_output={
                            (
                                this.props as SmallExplorationSectionProps & {
                                    exploration_output: any;
                                }
                            ).exploration_output
                        }
                    />
                ) : null}
            </Fragment>
        );
    }
}
