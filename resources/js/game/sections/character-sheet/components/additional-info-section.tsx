import React from "react";
import Dialogue from "../../../components/ui/dialogue/dialogue";
import {AdditionalInfoProps} from "./types/additional-info-props";
import Tabs from "../../../components/ui/tabs/tabs";
import TabPanel from "../../../components/ui/tabs/tab-panel";
import {formatNumber} from "../../../lib/game/format-number";
import Ajax from "../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from 'axios';
import ComponentLoading from "../../../components/ui/loading/component-loading";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";

export default class AdditionalInfoSection extends React.Component<AdditionalInfoProps, any> {

    private tabs: {key: string, name: string}[];

    constructor(props: AdditionalInfoProps) {
        super(props);

        this.tabs = [{
            key: 'stats',
            name: 'Stats'
        }, {
            key: 'holy',
            name: 'Holy',
        }, {
            key: 'voidance',
            name: 'Voidance'
        }, {
            key: 'ambush-counter',
            name: 'Ambush & Counter.'
        }];

        this.state = {
            is_loading: true,
            stat_details: [],
            error_message: '',
        }
    }

    componentDidMount() {
        if (this.props.character === null) {
            return;
        }

        (new Ajax).setRoute('character-sheet/'+this.props.character.id+'/stat-details').doAjaxCall('get', (response: AxiosResponse) => {
            this.setState({
                is_loading: false,
                stat_details: response.data.stat_details
            });
        }, (error: AxiosError) => {
            this.setState({ is_loading: false});
            if (typeof error.response !== 'undefined') {
                this.setState({
                    error_message: error.response.data.message,
                });
            }
        });
    }

    render() {

        if (this.props.character === null) {
            return null;
        }

        if (this.state.is_loading) {
            return <LoadingProgressBar />
        }

        return (
            <Tabs tabs={this.tabs} full_width={true}>
                <TabPanel key={'stats'}>
                    <div className='max-h-[350px] md:max-h-full overflow-y-scroll md:overflow-y-visible'>
                        <div className='grid md:grid-cols-2 gap-2'>
                            <div>
                                <dl>
                                    <dt>Raw Str</dt>
                                    <dd>{formatNumber(this.state.stat_details.str)}</dd>
                                    <dt>Raw Dex</dt>
                                    <dd>{formatNumber(this.state.stat_details.dex)}</dd>
                                    <dt>Raw Agi</dt>
                                    <dd>{formatNumber(this.state.stat_details.agi)}</dd>
                                    <dt>Raw Chr</dt>
                                    <dd>{formatNumber(this.state.stat_details.chr)}</dd>
                                    <dt>Raw Dur</dt>
                                    <dd>{formatNumber(this.state.stat_details.dur)}</dd>
                                    <dt>Raw Int</dt>
                                    <dd>{formatNumber(this.state.stat_details.int)}</dd>
                                    <dt>Raw Focus</dt>
                                    <dd>{formatNumber(this.state.stat_details.focus)}</dd>
                                </dl>
                            </div>
                            <div className='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                            <div>
                                <dl>
                                    <dt>Modded Str</dt>
                                    <dd>{formatNumber(this.state.stat_details.str_modded)}</dd>
                                    <dt>Modded Dex</dt>
                                    <dd>{formatNumber(this.state.stat_details.dex_modded)}</dd>
                                    <dt>Modded Agi</dt>
                                    <dd>{formatNumber(this.state.stat_details.agi_modded)}</dd>
                                    <dt>Modded Chr</dt>
                                    <dd>{formatNumber(this.state.stat_details.chr_modded)}</dd>
                                    <dt>Modded Dur</dt>
                                    <dd>{formatNumber(this.state.stat_details.dur_modded)}</dd>
                                    <dt>Modded Int</dt>
                                    <dd>{formatNumber(this.state.stat_details.int_modded)}</dd>
                                    <dt>Modded Focus</dt>
                                    <dd>{formatNumber(this.state.stat_details.focus_modded)}</dd>
                                </dl>
                            </div>
                        </div>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                        <div className='grid md:grid-cols-2 gap-2'>
                            <div>
                                <dl>
                                    <dt>Weapon Damage</dt>
                                    <dd>{formatNumber(this.state.stat_details.weapon_attack)}</dd>
                                    <dt>Ring Damage</dt>
                                    <dd>{formatNumber(this.state.stat_details.ring_damage)}</dd>
                                    <dt>Spell Damage</dt>
                                    <dd>{formatNumber(this.state.stat_details.spell_damage)}</dd>
                                    <dt>Healing Amount</dt>
                                    <dd>{formatNumber(this.state.stat_details.healing_amount)}</dd>
                                </dl>
                            </div>
                            <div className='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                            <div>
                                <dl>
                                    <dt>Voided Weapon Damage <sup>*</sup></dt>
                                    <dd>{formatNumber(this.state.stat_details.voided_weapon_attack)}</dd>
                                    <dt>Voided Ring Damage <sup>*</sup></dt>
                                    <dd>{formatNumber(this.state.stat_details.ring_damage)}</dd>
                                    <dt>Voided Spell Damage <sup>*</sup></dt>
                                    <dd>{formatNumber(this.state.stat_details.voided_spell_damage)}</dd>
                                    <dt>Voided Healing Amount <sup>*</sup></dt>
                                    <dd>{formatNumber(this.state.stat_details.voided_healing_amount)}</dd>
                                </dl>
                            </div>
                        </div>
                        <p className='mt-4 mb-2'>
                            <sup>*</sup> For more information please see <a href='/information/voidance' target='_blank'>Voidance Help <i
                            className="fas fa-external-link-alt"></i></a>
                        </p>
                    </div>
                </TabPanel>
                <TabPanel key={'holy'}>
                    <dl>
                        <dt>Holy Bonus:</dt>
                        <dt>{(this.state.stat_details.holy_bonus * 100).toFixed(2)}%</dt>
                        <dt>Holy Stacks:</dt>
                        <dt>{this.state.stat_details.current_stacks} / {this.props.character.max_holy_stacks}</dt>
                        <dt>Holy Attack Bonus:</dt>
                        <dt>{(this.state.stat_details.holy_attack_bonus * 100).toFixed(2)}%</dt>
                        <dt>Holy AC Bonus:</dt>
                        <dt>{(this.state.stat_details.holy_ac_bonus * 100).toFixed(2)}%</dt>
                        <dt>Holy Healing Bonus:</dt>
                        <dt>{(this.state.stat_details.holy_healing_bonus * 100).toFixed(2)}%</dt>
                    </dl>
                    <p className='mt-4'>
                        For more information please see <a href='/information/holy-items' target='_blank'>Holy Items Help <i
                        className="fas fa-external-link-alt"></i></a>
                    </p>
                </TabPanel>
                <TabPanel key={'voidance'}>
                    <dl>
                        <dt>Devouring Light:</dt>
                        <dt>{(this.state.stat_details.devouring_light * 100).toFixed(2)}%</dt>
                        <dt>Devouring Light Res.:</dt>
                        <dt>{(this.state.stat_details.devouring_light_res * 100).toFixed(2)}%</dt>
                        <dt>Devouring Darkness:</dt>
                        <dt>{(this.state.stat_details.devouring_darkness * 100).toFixed(2)}%</dt>
                        <dt>Devouring Darkness Res.:</dt>
                        <dt>{(this.state.stat_details.devouring_darkness_res * 100).toFixed(2)}%</dt>
                    </dl>
                    <p className='mt-4'>
                        For more information please see <a href='/information/voidance' target='_blank'>Voidance Help <i
                        className="fas fa-external-link-alt"></i></a>
                    </p>
                </TabPanel>
                <TabPanel key={'ambush-counter'}>
                    <dl>
                        <dt>Ambush Chance</dt>
                        <dd>{(this.state.stat_details.ambush_chance * 100).toFixed(2)}%</dd>
                        <dt>Ambush Resistance</dt>
                        <dd>{(this.state.stat_details.ambush_resistance_chance * 100).toFixed(2)}%</dd>
                        <dt>Counter Chance</dt>
                        <dd>{(this.state.stat_details.counter_chance * 100).toFixed(2)}%</dd>
                        <dt>Counter Resistance</dt>
                        <dd>{(this.state.stat_details.counter_resistance_chance * 100).toFixed(2)}%</dd>
                    </dl>
                    <p className='mt-4'>
                        For more information please see <a href='/information/ambush-and-counter' target='_blank'>Ambush and Counter Help <i
                        className="fas fa-external-link-alt"></i></a>
                    </p>
                </TabPanel>
            </Tabs>
        );
    }
}
