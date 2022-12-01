import React, {Fragment} from "react";
import Table from "../../../components/ui/data-tables/table";
import {formatNumber} from "../../../lib/game/format-number";
import {watchForDarkModeClassRankChange} from "../../../lib/game/dark-mode-watcher";
import Ajax from "../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import ComponentLoading from "../../../components/ui/loading/component-loading";
import CharacterClassRanksState
    from "../../../lib/game/character-sheet/types/class-ranks/types/character-class-ranks-state";
import GameClassType from "../../../lib/game/character-sheet/types/class-ranks/game-class-type";
import ClassRankType from "../../../lib/game/character-sheet/types/class-ranks/class-rank-type";
import BuildingDetails from "../../../lib/game/kingdoms/building-details";

export default class CharacterClassRanks extends React.Component<any, CharacterClassRanksState> {

    constructor(props: any) {
        super(props);

        this.state = {
            class_ranks: [],
            dark_tables: false,
            loading: true,
            open_class_details: false,
            class_name_selected: null,
        }
    }

    componentDidMount() {
        watchForDarkModeClassRankChange(this);

        (new Ajax()).setRoute('class-ranks/' + this.props.character.id).doAjaxCall('get', (response: AxiosResponse) => {
            this.setState({
                class_ranks: response.data.class_ranks,
                loading: false,
            });
        }, (error: AxiosError) => {
            console.error(error);
        });
    }

    manageViewClass(className: string|null) {
        const classNameSelected: ClassRankType = this.state.class_ranks.filter((rank) => rank.class_name === className)[0];
        this.setState({
            open_class_details: !this.state.open_class_details,
            class_name_selected: classNameSelected,
        })
    }

    tableColumns() {
        return [
            {
                name: 'Class name',
                selector: (row: { class_name: string; }) => row.class_name,
                cell: (row: any) =>
                    <button onClick={() => this.manageViewClass(row.class_name)}
                            className={'hover:underline text-blue-500 dark:text-blue-400'}>
                        {row.class_name}
                    </button>
            },
            {
                name: 'Rank Level',
                selector: (row: { level: number; }) => row.level,
                sortable: true,
            },
            {
                name: 'XP',
                selector: (row: { current_xp: number; required_xp: number }) => row.current_xp,
                cell: (row: any) => <span>
                    { formatNumber(row.current_xp) + '/' + formatNumber(row.required_xp) }
                </span>
            },
            {
                name: 'Active',
                selector: (row: { is_active: boolean; }) => row.is_active,
                cell: (row: any) => <span>
                    { row.is_active ? 'Yes' : 'No' }
                </span>
            },
            {
                name: 'Is Locked',
                selector: (row: { is_locked: boolean; }) => row.is_locked,
                cell: (row: any) => <span>
                    { row.is_locked ? 'Yes' : 'No' }
                </span>
            }
        ];
    }

    render() {
        if (this.state.loading) {
            return (
                <div className='relative my-6 p-[20px]'>
                    <ComponentLoading />
                </div>
            )
        }

        return (
            <div className='max-h-[375px] overflow-y-auto lg:overflow-y-hidden lg:max-h-full'>

                {
                    this.state.open_class_details && this.state.class_name_selected !== null ?
                        <Fragment>
                            <div className='text-right cursor-pointer text-red-500 position top-[-10px]'>
                                <button onClick={() => this.manageViewClass(null)}><i className="fas fa-minus-circle"></i></button>
                            </div>

                            <h2 className='text-sky-700 dark:text-sky-500 font-bold my-4'>
                                {this.state.class_name_selected.class_name}
                            </h2>

                            <p className='mb-4'>
                                To learn more about this class, checkout <a href="/information/reincarnation" target="_blank">the class documentation <i className="fas fa-external-link-alt"></i></a> to
                                learn more about this class including tips and tricks to maximize damage output and unlock the special attack.
                            </p>

                            <p className='mb-4'>
                                Your stats will not change when you switch to this class. This is just a general break down of the class. Special clases are different,
                                these will give you % boosts to your stats as a modifier while you have this class enabled.
                            </p>

                            <p className='mb-4'>
                                When you switch to this class, your current class skill be hidden and you will now have an opportunity to level this classes skill
                                in the skill section for trainable skills. Click the above link to learn more about the class.
                            </p>

                            <div className='grid lg:grid-cols-2 gap-2 mb-4'>
                                <div>
                                    <h3 className='my-3'>Base Information</h3>
                                    <dl>
                                        <dt>Base Damage Stat</dt>
                                        <dd>{this.state.class_name_selected.game_class.to_hit_stat}</dd>
                                        <dt>Str Mod (pts.)</dt>
                                        <dd>+{this.state.class_name_selected.game_class.str_mod}</dd>
                                        <dt>Dex Mod (pts.)</dt>
                                        <dd>+{this.state.class_name_selected.game_class.dex_mod}</dd>
                                        <dt>Dur Mod (pts.)</dt>
                                        <dd>+{this.state.class_name_selected.game_class.dur_mod}</dd>
                                        <dt>Int Mod (pts.)</dt>
                                        <dd>+{this.state.class_name_selected.game_class.int_mod}</dd>
                                        <dt>Agi Mod (pts.)</dt>
                                        <dd>+{this.state.class_name_selected.game_class.agi_mod}</dd>
                                        <dt>Chr Mod (pts.)</dt>
                                        <dd>+{this.state.class_name_selected.game_class.chr_mod}</dd>
                                        <dt>Focus Mod (pts.)</dt>
                                        <dd>+{this.state.class_name_selected.game_class.focus_mod}</dd>
                                    </dl>
                                </div>
                                <div className='border-b-2 block lg:hidden border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                                <div>
                                    <h3 className='my-3'>Skill Modifiers</h3>
                                    <dl>
                                        <dt>Accuracy Mod</dt>
                                        <dd>+{(this.state.class_name_selected.game_class.accuracy_mod * 100).toFixed(2)}%</dd>
                                        <dt>Looting Mod</dt>
                                        <dd>+{(this.state.class_name_selected.game_class.accuracy_mod * 100).toFixed(2)}%</dd>
                                    </dl>
                                </div>
                            </div>
                        </Fragment>
                    :
                        <Table
                            data={this.state.class_ranks}
                            columns={this.tableColumns()}
                            dark_table={this.props.dark_tables}
                        />
                }
            </div>
        );
    }
}
