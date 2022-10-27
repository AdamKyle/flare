import React, {Fragment} from "react";
import SmallMapSectionProps
    from "../../../../lib/game/types/actions/components/smaller-actions/small-map-section-props";
import MapSection from "../../../map/map-section";
import PositionType from "../../../../lib/game/types/map/position-type";


export default class SmallMapMovementActions extends React.Component<SmallMapSectionProps, any> {

    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <div className='relative'>
                <button type='button' onClick={this.props.close_map_section}
                        className='text-red-600 dark:text-red-500 absolute right-[-20px] top-[-25px]'
                >
                    <i className="fas fa-times-circle"></i>
                </button>
                <MapSection
                    user_id={this.props.character.user_id}
                    character_id={this.props.character.id}
                    view_port={this.props.view_port}
                    currencies={this.props.character_currencies}
                    is_dead={this.props.character.is_dead}
                    is_automaton_running={this.props.character.is_automation_running}
                    automation_completed_at={this.props.character.automation_completed_at}
                    show_celestial_fight_button={this.props.update_celestial}
                    set_character_position={this.props.update_character_position}
                    update_character_quests_plane={this.props.update_plane_quests}
                    disable_bottom_timer={true}
                />
            </div>
        )
    }
}