import React, {Fragment} from "react";
import CharacterTopSectionProps from "../../lib/game/character-top-section/character-top-section-props";
import CharacterTopSectionState from "../../lib/game/character-top-section/character-top-section-state";
import ComponentLoading from "../../components/ui/loading/component-loading";
import Ajax from "../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import {removeCommas} from "../../lib/game/format-number";

export default class CharacterTopSection extends React.Component<CharacterTopSectionProps, CharacterTopSectionState> {
    constructor(props: CharacterTopSectionProps) {
        super(props);

        this.state = {
            hide_top_bar: false,
            loading: true,
            character: null,
        }
    }

    hideTopBar() {
        this.setState({
            hide_top_bar: !this.state.hide_top_bar
        });
    }

    componentDidMount() {
        (new Ajax()).setRoute('character-sheet/' + this.props.character_id).doAjaxCall('get', (result: AxiosResponse) => {

           this.setState({
               loading: false,
               character: result.data.sheet
           }, () => {
                this.props.update_character_status({
                    is_dead: result.data.sheet.is_dead,
                    can_adventure: result.data.sheet.can_adventure,
                });

               this.props.update_character_currencies({
                   gold: removeCommas(result.data.sheet.gold),
                   gold_dust: removeCommas(result.data.sheet.gold_dust),
                   shards: removeCommas(result.data.sheet.shards),
                   copper_coins: removeCommas(result.data.sheet.copper_coins),
               });
           });
        }, (err: AxiosError) => {

        });
    }

    componentDidUpdate(prevProps: CharacterTopSectionProps, prevState: CharacterTopSectionState) {
        if (this.props.view_port >= 1024 && this.state.hide_top_bar) {
            this.setState({
                hide_top_bar: false
            });
        }

        if (this.props.view_port < 1024 && !this.state.hide_top_bar) {
            this.setState({
                hide_top_bar: true
            });
        }
    }

    getXpPercentage(xp: number|undefined): number {

        if (typeof xp !== 'undefined') {
            return xp;
        }

        return 0;
    }

    abbreviateNumber(stat: string|undefined): string|number {

        if (typeof stat === 'undefined') {
            return 0;
        }

        let statNumber = parseInt(stat.replace(/,/g, ''))

        const symbol = ["", "k", "M", "B", "T", "Quad.", "Qunit."];

        // what tier? (determines SI symbol)
        var tier = Math.log10(Math.abs(statNumber)) / 3 | 0;

        // if zero, we don't need a suffix
        if(tier == 0) return statNumber;

        // get suffix and determine scale
        var suffix = symbol[tier];
        var scale = Math.pow(10, tier * 3);

        // scale the number
        var scaled = statNumber / scale;

        // format number and add suffix
        return scaled.toFixed(1) + suffix;
    }

    render() {
        if (this.state.loading) {
            return <ComponentLoading />
        }

        if (this.state.hide_top_bar) {
            return (
                <Fragment>
                    <div className='grid grid-cols-2'>
                        <span><strong>Character Details</strong></span>
                        <div className='text-right cursor-pointer text-blue-500'>
                            <button onClick={this.hideTopBar.bind(this)}><i className="fas fa-plus-circle"></i></button>
                        </div>
                    </div>

                    <div className='relative top-[24px]'>
                        <div className="flex justify-between mb-1">
                            <span className="font-medium text-orange-700 dark:text-white text-xs">XP (Current Level: {this.state.character?.level}/{this.state.character?.max_level})</span>
                            <span className="text-xs font-medium text-orange-700 dark:text-white">{this.state.character?.xp}/100</span>
                        </div>
                        <div className="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700">
                            <div className="bg-orange-600 h-1.5 rounded-full" style={{width: this.getXpPercentage(this.state.character?.xp) + '%'}}></div>
                        </div>
                    </div>
                </Fragment>
            );
        }

        return (
            <Fragment>
                <div className='grid grid-cols-2 sm:grid-cols-1'>
                    <div>
                        <div className='grid sm:grid-cols-4'>
                            <span><strong>Name</strong>: {this.state.character?.name}</span>
                            <span><strong>Race</strong>: {this.state.character?.race}</span>
                            <span><strong>Class</strong>: {this.state.character?.class}</span>
                            <span><strong>Gold</strong>: {this.state.character?.gold}</span>
                        </div>
                        <div className='border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 hidden sm:block'></div>
                        <div className='grid sm:grid-cols-3'>
                            <span><strong>Gold Dust</strong>: {this.state.character?.gold_dust}</span>
                            <span><strong>Crystal Shards</strong>: {this.state.character?.shards}</span>
                            <span><strong>Copper Coins</strong>: {this.state.character?.copper_coins}</span>
                        </div>
                        <div className='border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 hidden sm:block'></div>
                        <div className='grid sm:grid-cols-4'>
                            <div>
                                <div className='py-1'><strong>Level</strong>: {this.state.character?.level}/{this.state.character?.max_level}</div>
                                <div className='py-1'><strong>AC</strong>: {this.abbreviateNumber(this.state.character?.ac)}</div>
                                <div className='py-1'><strong>Attack</strong>: {this.abbreviateNumber(this.state.character?.attack)}</div>
                                <div className='py-1'><strong>Health</strong>: {this.abbreviateNumber(this.state.character?.health)}</div>
                            </div>
                            <div>
                                <div className='py-1'><strong>Strength</strong>: {this.abbreviateNumber(this.state.character?.str_modded)}</div>
                                <div className='py-1'><strong>Durability</strong>: {this.abbreviateNumber(this.state.character?.dur_modded)}</div>
                                <div className='py-1'><strong>Dexterity</strong>: {this.abbreviateNumber(this.state.character?.dex_modded)}</div>
                            </div>
                            <div>
                                <div className='py-1'><strong>Intelligence</strong>: {this.abbreviateNumber(this.state.character?.int_modded)}</div>
                                <div className='py-1'><strong>Charisma</strong>: {this.abbreviateNumber(this.state.character?.chr_modded)}</div>
                                <div className='py-1'><strong>Focus</strong>: {this.abbreviateNumber(this.state.character?.focus_modded)}</div>
                            </div>
                            <div>
                                <div className='py-1'><strong>Agility</strong>: {this.abbreviateNumber(this.state.character?.agi_modded)}</div>
                            </div>
                        </div>
                    </div>
                    <div className='text-right cursor-pointer text-red-500 block sm:hidden'>
                        <button onClick={this.hideTopBar.bind(this)}><i className="fas fa-minus-circle"></i></button>
                    </div>
                </div>
                <div className='relative top-[24px]'>
                    <div className="flex justify-between mb-1">
                        <span className="font-medium text-orange-700 dark:text-white text-xs">XP</span>
                        <span className="text-xs font-medium text-orange-700 dark:text-white">{this.state.character?.xp}/100</span>
                    </div>
                    <div className="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700">
                        <div className="bg-orange-600 h-1.5 rounded-full" style={{width: this.getXpPercentage(this.state.character?.xp) + '%'}}></div>
                    </div>
                </div>
            </Fragment>
        )
    }
}
