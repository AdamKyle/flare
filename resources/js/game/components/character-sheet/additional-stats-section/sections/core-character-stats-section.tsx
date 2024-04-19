import React from "react";
import {AdditionalInfoProps} from "../../../../sections/character-sheet/components/types/additional-info-props";
import {formatNumber} from "../../../../lib/game/format-number";
import Ajax from "../../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from 'axios';
import LoadingProgressBar from "../../../ui/progress-bars/loading-progress-bar";
import DropDown from "../../../ui/drop-down/drop-down";

export default class CoreCharacterStatsSection extends React.Component<AdditionalInfoProps, any> {

    constructor(props: AdditionalInfoProps) {
        super(props);

        this.state = {
            is_loading: true,
            stat_details: [],
            error_message: '',
            stat_type_to_show: '',
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

    setFilterType(type: string): void {
        this.setState({
            stat_type_to_show: type
        })
    }

    createTypeFilterDropDown() {
        return [
            {
                name: "Core Stats",
                icon_class: "ra ra-muscle-fat",
                on_click: () => this.setFilterType('core-stats'),
            },
            {
                name: "Holy",
                icon_class: "ra ra-level-three",
                on_click: () => this.setFilterType('holy'),
            },
            {
                name: "Ambush & Counter",
                icon_class: "ra ra-blade-bite",
                on_click: () => this.setFilterType('ambush'),
            },
            {
                name: "Voidance",
                icon_class: "ra ra-double-team",
                on_click: () => this.setFilterType('voidance'),
            },
        ];
    }

    renderStatDetails() {
        return (
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
                <div className='grid md:grid-cols-3 gap-2'>
                    <div>
                        <dl>
                            <dt>Health</dt>
                            <dd>{formatNumber(this.state.stat_details.health)}</dd>
                            <dt>Voided Health</dt>
                            <dd>{formatNumber(this.state.stat_details.voided_health)}</dd>
                            <dt>AC</dt>
                            <dd>{formatNumber(this.state.stat_details.ac)}</dd>
                            <dt>Voided AC</dt>
                            <dd>{formatNumber(this.state.stat_details.voided_ac)}</dd>
                        </dl>
                    </div>
                    <div
                        className='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-3'></div>

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
                    <div
                        className='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-3'></div>
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
                    <sup>*</sup> For more information please see <a href='/information/voidance'
                                                                    target='_blank'>Voidance Help <i
                    className="fas fa-external-link-alt"></i></a>
                </p>
            </div>
        );
    }

    renderHolySection() {

        if (this.props.character === null) {
            return;
        }

        return(
            <div>
                <p className='mt-3 mb-6'>
                    Holy comes from crafting Alchemy items such as Holy Oils which can then be applied to a characters item
                    to increase the stats you see below, which then apply to your character over all.
                </p>
                <dl>
                    <dt>Holy Bonus:</dt>
                    <dt>{(this.state.stat_details.holy_bonus * 100).toFixed(2)}%</dt>
                    <dt>Holy Stacks:</dt>
                    <dt>{this.state.stat_details.current_stacks} / {this.state.stat_details.max_holy_stacks}</dt>
                    <dt>Holy Attack Bonus:</dt>
                    <dt>{(this.state.stat_details.holy_attack_bonus * 100).toFixed(2)}%</dt>
                    <dt>Holy AC Bonus:</dt>
                    <dt>{(this.state.stat_details.holy_ac_bonus * 100).toFixed(2)}%</dt>
                    <dt>Holy Healing Bonus:</dt>
                    <dt>{(this.state.stat_details.holy_healing_bonus * 100).toFixed(2)}%</dt>
                </dl>
                <p className='mt-4'>
                    For more information please see <a href='/information/holy-items' target='_blank'>Holy Items
                    Help <i
                        className="fas fa-external-link-alt"></i></a>
                </p>
            </div>
        )
    }

    renderVoidanceSection() {
        return (
            <div>
                <p className='mt-3'>
                    Devouring Light and Devouring Darkness are considered Void and Devoid. These come from Quest items
                    and Enchantments. Some planes will completely remove your ability to void and devoid enemies.
                </p>
                <p className='my-3'>
                    Voiding (Devouring Light) means none of your or the enemies enchantments can fire. This can
                    completely
                    wreck a player if they get voided by a mid game to late game creature.
                </p>
                <p className='mb-6'>
                    Devoiding (Devouring Darkness) means that you or the enemy have stopped the other from being able to void you.
                    For example if you are devoted, you cannot void the enemy and vice versa.
                </p>
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
                    For more information please see <a href='/information/voidance' target='_blank'>Voidance
                    Help <i
                        className="fas fa-external-link-alt"></i></a>
                </p>
            </div>
        )
    }

    renderAmbushCounter() {
        return (
            <div>
                <p className='my-3'>
                    Ambush and Counter chance come from trinkets, which raise your chance to ambush an enemy before the
                    fight begins or to
                    counter an enemies attack.
                </p>
                <p className='mb-6'>
                    Ambush and Counter resistance also come from trinkets and increase your chance to resist late game
                    creatures ability to
                    ambush you before the battle starts or counter your attacks when you attack.
                </p>
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
                    For more information please see <a href='/information/ambush-and-counter' target='_blank'>Ambush
                    and Counter Help <i
                        className="fas fa-external-link-alt"></i></a>
                </p>
            </div>
        )
    }

    renderSection() {
        switch (this.state.stat_type_to_show) {
            case 'core-stats':
                return this.renderStatDetails()
            case 'holy':
                return this.renderHolySection()
            case 'ambush':
                return this.renderAmbushCounter()
            case 'voidance':
                return this.renderVoidanceSection()
            default:
                return this.renderStatDetails();
        }
    }

    render() {

        if (this.props.character === null) {
            return null;
        }

        if (this.state.is_loading) {
            return <LoadingProgressBar/>
        }

        return (
            <div>
                <div className='my-4 max-w-full md:max-w-[25%]'>
                    <DropDown
                        menu_items={this.createTypeFilterDropDown()}
                        button_title={"Stat Type"}
                    />
                </div>

                {
                    this.renderSection()
                }
            </div>
        );
    }
}
