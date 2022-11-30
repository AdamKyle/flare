import React, {Fragment} from "react";
import Table from "../../../components/ui/data-tables/table";
import {formatNumber} from "../../../lib/game/format-number";
import {watchForDarkModeClassRankChange} from "../../../lib/game/dark-mode-watcher";
import Ajax from "../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import ComponentLoading from "../../../components/ui/loading/component-loading";

export default class CharacterClassRanks extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            class_ranks: [],
            dark_tables: false,
            loading: true,
            open_class_details: false,
            class_name_selected: '',
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

    manageViewClass(className: string) {
        this.setState({
            open_class_details: !this.state.open_class_details,
            class_name_selected: className
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
            return <ComponentLoading />
        }

        return (
            <div className='max-h-[375px] overflow-y-auto lg:overflow-y-hidden lg:max-h-full'>


                {
                    this.state.open_class_details ?
                        <Fragment>
                            <div className='text-right cursor-pointer text-red-500 position top-[-10px]'>
                                <button onClick={() => this.manageViewClass('')}><i className="fas fa-minus-circle"></i></button>
                            </div>

                            Hello World {this.state.class_name_selected}
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
