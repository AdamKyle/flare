import React from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import {AdditionalInfoModalProps} from "../../../../lib/game/character-sheet/types/modal/additional-info-modal-props";
import Tabs from "../../../../components/ui/tabs/tabs";
import TabPanel from "../../../../components/ui/tabs/tab-panel";
import {formatNumber} from "../../../../lib/game/format-number";

export default class CharacterResistances extends React.Component<AdditionalInfoModalProps, any> {


    constructor(props: AdditionalInfoModalProps) {
        super(props);
    }

    render() {

        if (this.props.character === null) {
            return null;
        }

        return (
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.manage_modal}
                      title={this.props.title}
                      secondary_actions={null}
            >
                <div>
                    <p className='mb-4'>
                        Resistances come from a variety of places. Click Additional Info to see other resistances such as Devouring light,
                        Ambush and counter.
                    </p>
                    <p className='mb-4'>
                        Rings will increase these values.
                    </p>
                    <dl>
                        <dt>Spell Evasions</dt>
                        <dd>{(this.props.character.spell_evasion * 100).toFixed(2)}%</dd>
                        <dt>Affix Damage Reduction</dt>
                        <dd>{(this.props.character.affix_damage_reduction * 100).toFixed(2)}%</dd>
                    </dl>
                </div>
            </Dialogue>
        );
    }
}
