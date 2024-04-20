import React from "react";
import {formatNumber} from "../../../../../../lib/game/format-number";
import PrimaryLinkButton from "../../../../../ui/buttons/primary-link-button";
import StatBreakDown from "../stat-break-down/stat-break-down";

export default class StatDetails extends React.Component<any, any> {
    constructor(props: any) {
        super(props);

        this.state = {
            show_detailed_section: false,
            details_type: null,
        }
    }

    showTypeDetails(type: string): void {
        this.setState({
            show_detailed_section: true,
            details_type: type,
        })
    }

    closeTypeDetails(): void {
        this.setState({
            show_detailed_section: false,
            details_type: null,
        })
    }

    render() {

        if (this.state.show_detailed_section && this.state.details_type !== null) {
            return  <StatBreakDown close_section={this.closeTypeDetails.bind(this)} type={this.state.details_type} character_id={this.props.character.id}/>
        }

        return (
            <div className='max-h-[350px] md:max-h-full overflow-y-scroll md:overflow-y-visible'>
                <div className='grid md:grid-cols-2 gap-2'>
                    <div>
                        <dl>
                            <dt>Raw Str</dt>
                            <dd>{formatNumber(this.props.stat_details.str)}</dd>
                            <dt>Raw Dex</dt>
                            <dd>{formatNumber(this.props.stat_details.dex)}</dd>
                            <dt>Raw Agi</dt>
                            <dd>{formatNumber(this.props.stat_details.agi)}</dd>
                            <dt>Raw Chr</dt>
                            <dd>{formatNumber(this.props.stat_details.chr)}</dd>
                            <dt>Raw Dur</dt>
                            <dd>{formatNumber(this.props.stat_details.dur)}</dd>
                            <dt>Raw Int</dt>
                            <dd>{formatNumber(this.props.stat_details.int)}</dd>
                            <dt>Raw Focus</dt>
                            <dd>{formatNumber(this.props.stat_details.focus)}</dd>
                        </dl>
                    </div>
                    <div className='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                    <div>
                        <dl>
                            <dt><PrimaryLinkButton button_label={'Modded Str'} on_click={() => {this.showTypeDetails('str')}} /></dt>
                            <dd>{formatNumber(this.props.stat_details.str_modded)}</dd>
                            <dt><PrimaryLinkButton button_label={'Modded Dex'} on_click={() => {this.showTypeDetails('dex')}} /></dt>
                            <dd>{formatNumber(this.props.stat_details.dex_modded)}</dd>
                            <dt><PrimaryLinkButton button_label={'Modded Agi'} on_click={() => {this.showTypeDetails('agi')}} /></dt>
                            <dd>{formatNumber(this.props.stat_details.agi_modded)}</dd>
                            <dt><PrimaryLinkButton button_label={'Modded Chr'} on_click={() => {this.showTypeDetails('chr')}} /></dt>
                            <dd>{formatNumber(this.props.stat_details.chr_modded)}</dd>
                            <dt><PrimaryLinkButton button_label={'Modded Dur'} on_click={() => {this.showTypeDetails('dur')}} /></dt>
                            <dd>{formatNumber(this.props.stat_details.dur_modded)}</dd>
                            <dt><PrimaryLinkButton button_label={'Modded Int'} on_click={() => {this.showTypeDetails('int')}} /></dt>
                            <dd>{formatNumber(this.props.stat_details.int_modded)}</dd>
                            <dt><PrimaryLinkButton button_label={'Modded Focus'} on_click={() => {this.showTypeDetails('focus')}} /></dt>
                            <dd>{formatNumber(this.props.stat_details.focus_modded)}</dd>
                        </dl>
                    </div>
                </div>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <div className='grid md:grid-cols-3 gap-2'>
                    <div>
                        <dl>
                            <dt><PrimaryLinkButton button_label={'Health'} on_click={() => {}} /></dt>
                            <dd>{formatNumber(this.props.stat_details.health)}</dd>
                            <dt>Voided Health</dt>
                            <dd>{formatNumber(this.props.stat_details.voided_health)}</dd>
                            <dt><PrimaryLinkButton button_label={'Ac'} on_click={() => {}} /></dt>
                            <dd>{formatNumber(this.props.stat_details.ac)}</dd>
                            <dt>Voided AC</dt>
                            <dd>{formatNumber(this.props.stat_details.voided_ac)}</dd>
                        </dl>
                    </div>
                    <div
                        className='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-3'></div>

                    <div>
                        <dl>
                            <dt><PrimaryLinkButton button_label={'Weapon Damage'} on_click={() => {}} /></dt>
                            <dd>{formatNumber(this.props.stat_details.weapon_attack)}</dd>
                            <dt><PrimaryLinkButton button_label={'Ring Damage'} on_click={() => {}} /></dt>
                            <dd>{formatNumber(this.props.stat_details.ring_damage)}</dd>
                            <dt><PrimaryLinkButton button_label={'Spell Damage'} on_click={() => {}} /></dt>
                            <dd>{formatNumber(this.props.stat_details.spell_damage)}</dd>
                            <dt><PrimaryLinkButton button_label={'Healing Amount'} on_click={() => {}} /></dt>
                            <dd>{formatNumber(this.props.stat_details.healing_amount)}</dd>
                        </dl>
                    </div>
                    <div
                        className='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                    <div>
                        <dl>
                            <dt>Voided Weapon Damage <sup>*</sup></dt>
                            <dd>{formatNumber(this.props.stat_details.voided_weapon_attack)}</dd>
                            <dt>Voided Ring Damage <sup>*</sup></dt>
                            <dd>{formatNumber(this.props.stat_details.ring_damage)}</dd>
                            <dt>Voided Spell Damage <sup>*</sup></dt>
                            <dd>{formatNumber(this.props.stat_details.voided_spell_damage)}</dd>
                            <dt>Voided Healing Amount <sup>*</sup></dt>
                            <dd>{formatNumber(this.props.stat_details.voided_healing_amount)}</dd>
                        </dl>
                    </div>
                </div>
                <p className='mt-4 mb-2'>
                    <sup>*</sup> For more information please see <a href='/information/voidance'
                                                                    target='_blank'>Voidance Help <i
                    className="fas fa-external-link-alt"></i></a>
                </p>
            </div>
        );
    }
}
