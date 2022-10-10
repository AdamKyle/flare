import React, {Fragment} from "react";
import CharacterTopSectionProps from "../../lib/game/character-top-section/character-top-section-props";
import CharacterTopSectionState from "../../lib/game/character-top-section/character-top-section-state";
import {formatNumber} from "../../lib/game/format-number";

export default class CharacterTopSection extends React.Component<CharacterTopSectionProps, CharacterTopSectionState> {
    constructor(props: CharacterTopSectionProps) {
        super(props);

        this.state = {
            hide_top_bar: true,
        }
    }

    hideTopBar() {
        this.setState({
            hide_top_bar: !this.state.hide_top_bar
        });
    }

    componentDidMount() {
    }

    componentDidUpdate(prevProps: CharacterTopSectionProps, prevState: CharacterTopSectionState) {
        if (this.props.view_port >= 1600 && this.state.hide_top_bar) {
            this.setState({
                hide_top_bar: false
            });
        }
    }

    getXpPercentage(): number {
        const xpNext    = this.props.character.xp_next;
        const currentXP = this.props.character.xp;

        return (currentXP/xpNext) * 100;
    }

    abbreviateNumber(stat: number|undefined): string|number {

        if (typeof stat === 'undefined') {
            return 0;
        }

        let statNumber = stat;

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
        if (this.props.character === null) {
            return null;
        }

        if (this.state.hide_top_bar && this.props.view_port < 1600) {
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
                            <span className="font-medium text-orange-700 dark:text-white text-xs">XP (Current Level: {this.props.character.level}/{this.props.character.max_level})</span>
                            <span className="text-xs font-medium text-orange-700 dark:text-white">{formatNumber(this.props.character.xp)}/{formatNumber(this.props.character.xp_next)}</span>
                        </div>
                        <div className="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700">
                            <div className="bg-orange-600 h-1.5 rounded-full" style={{width: this.getXpPercentage() + '%'}}></div>
                        </div>
                    </div>
                </Fragment>
            );
        }

        return (
            <Fragment>
                <div className='text-right cursor-pointer text-red-500 block lg:hidden'>
                    <button onClick={this.hideTopBar.bind(this)}><i className="fas fa-minus-circle"></i></button>
                </div>

                <div className='grid lg:grid-cols-4'>
                    <span className={'mb-2 sm:mb-0'}><strong>Name</strong>: {this.props.character.name}</span>
                    <span className={'mb-2 sm:mb-0'}><strong>Race</strong>: <a href={"/information/race/" + this.props.character.race_id} target='_blank'>{this.props.character.race}</a></span>
                    <span className={'mb-2 sm:mb-0'}><strong>Class</strong>: <a href={"/information/class/" + this.props.character.class_id} target='_blank'>{this.props.character.class}</a></span>
                    <span className={'mb-2 sm:mb-0'}><strong>Gold</strong>: {this.props.character.gold}</span>
                </div>
                <div className='border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 hidden sm:block'></div>
                <div className='grid lg:grid-cols-3'>
                    <span className={'mb-2 sm:mb-0'}><strong>Gold Dust</strong>: {this.props.character.gold_dust}</span>
                    <span className={'mb-2 sm:mb-0'}><strong>Crystal Shards</strong>: {this.props.character.shards}</span>
                    <span className={'mb-2 sm:mb-0'}><strong>Copper Coins</strong>: {this.props.character.copper_coins}</span>
                </div>
                <div className='border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 hidden sm:block'></div>
                <div className='grid lg:grid-cols-4'>
                    <div>
                        <div className='py-1'><strong>Level</strong>: {this.props.character.level}/{this.props.character.max_level}</div>
                        <div className='py-1'><strong>AC</strong>: {this.abbreviateNumber(this.props.character.ac)}</div>
                        <div className='py-1'><strong>Attack</strong>: {this.abbreviateNumber(this.props.character.attack)}</div>
                        <div className='py-1'><strong>Health</strong>: {this.abbreviateNumber(this.props.character.health)}</div>
                    </div>
                    <div>
                        <div className='py-1'><strong>Strength</strong>: {this.abbreviateNumber(this.props.character.str_modded)}</div>
                        <div className='py-1'><strong>Durability</strong>: {this.abbreviateNumber(this.props.character.dur_modded)}</div>
                        <div className='py-1'><strong>Dexterity</strong>: {this.abbreviateNumber(this.props.character.dex_modded)}</div>
                    </div>
                    <div>
                        <div className='py-1'><strong>Intelligence</strong>: {this.abbreviateNumber(this.props.character.int_modded)}</div>
                        <div className='py-1'><strong>Charisma</strong>: {this.abbreviateNumber(this.props.character.chr_modded)}</div>
                        <div className='py-1'><strong>Focus</strong>: {this.abbreviateNumber(this.props.character.focus_modded)}</div>
                    </div>
                    <div>
                        <div className='py-1'><strong>Agility</strong>: {this.abbreviateNumber(this.props.character.agi_modded)}</div>
                    </div>
                </div>
                <div className='relative top-[24px]'>
                    <div className="flex justify-between mb-1">
                        <span className="font-medium text-orange-700 dark:text-white text-xs">XP</span>
                        <span className="text-xs font-medium text-orange-700 dark:text-white">{formatNumber(this.props.character.xp)}/{formatNumber(this.props.character.xp_next)}</span>
                    </div>
                    <div className="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700">
                        <div className="bg-orange-600 h-1.5 rounded-full" style={{width: this.getXpPercentage() + '%'}}></div>
                    </div>
                </div>
            </Fragment>
        )
    }
}
