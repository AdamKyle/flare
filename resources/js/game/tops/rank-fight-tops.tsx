import React, {Fragment} from "react";
import BasicCard from "../components/ui/cards/basic-card";
import ComponentLoading from "../components/ui/loading/component-loading";
import {AxiosError, AxiosResponse} from "axios";
import Ajax from "../lib/ajax/ajax";
import CharacterRankedFightTopsChart from "./charts/character-ranked-fight-tops-chart";
import Select from "react-select";
import LoadingProgressBar from "../components/ui/progress-bars/loading-progress-bar";
import Table from "../components/ui/data-tables/table";
import {watchForDarkModeRankFightTopsChange} from "../lib/game/dark-mode-watcher";

export default class RankFightTops extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            chart_data: [],
            list_data: [],
            current_rank: 0,
            rank_selected: 0,
            fetching_rank: false,
            dark_tables: false,
        }
    }

    componentDidMount() {
        watchForDarkModeRankFightTopsChange(this);

        (new Ajax()).setRoute('ranked-fight-tops').doAjaxCall('get', (response: AxiosResponse) => {
            this.setState({
                loading: false,
                chart_data: response.data.character_tops_chart,
                current_rank: response.data.current_rank,
            });
        }, (error: AxiosError) => {

        })
    }

    setRank(data: any) {

        if (data.value === 0) {
            return;
        }

        this.setState({
            fetching_rank: true,
            rank_selected: data.value,
        }, () => {
            (new Ajax()).setRoute('rank-fight-tops-list').setParameters({
                rank: data.value,
            }).doAjaxCall('get', (response: AxiosResponse) => {
                this.setState({
                    fetching_rank: false,
                    list_data: response.data.rank_data,
                });
            }, (error: AxiosError) => {

            })
        })
    }

    options() {
        const ranks = [];

        for (let i = 1; i <= this.state.current_rank; i++) {
            ranks.push({
                label: 'Rank ' + i,
                value: i,
            });
        }

        return ranks;
    }

    getSelectedRank() {
        if (this.state.rank_selected === 0) {
            return [{
                label: 'Please select a rank',
                value: 0,
            }]
        }

        return [{label: 'Rank ' + this.state.rank_selected, value: this.state.rank_selected}];
    }


    renderRankSelection() {
        return (
            <div className='my-4'>
                <Select
                    onChange={this.setRank.bind(this)}
                    options={this.options()}
                    menuPosition={'absolute'}
                    menuPlacement={'bottom'}
                    styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                    menuPortalTarget={document.body}
                    value={this.getSelectedRank()}
                />
            </div>
        )
    }

    renderList() {
        if (this.state.list_data.length === 0) {
            return null;
        }

        return this.state.list_data.map((item: any) => {
            return (
                <Fragment>
                    <dt>Character Name:</dt>
                    <dd>{item.character_name}</dd>
                    <dt>Achieved At:</dt>
                    <dd>{item.date}</dd>
                </Fragment>
            )
        })
    }

    renderRankSection() {
        return (
            <Fragment>
                <div className='my-4'>
                    <CharacterRankedFightTopsChart data={this.state.chart_data.data}
                                                   labels={this.state.chart_data.labels}/>
                </div>
                <div className='mx-auto w-full md:w-1/2'>
                    {this.renderRankSelection()}

                    {
                        this.state.fetching_rank ?
                            <div className={'my-4'}>
                                <LoadingProgressBar />
                            </div>
                        : null
                    }

                </div>
                {
                    this.state.list_data.length > 0 ?
                        <div className='mx-auto w-full md:w-1/2'>
                            <h3 className='my-2'>Rank {this.state.rank_selected}</h3>
                            <Table data={this.state.list_data}
                                   columns={[
                                       {
                                           name: 'Character Name',
                                           selector: (row: any) => row.character_name,
                                           cell: (row: any) => <a href={'/game/tops/' + row.character_id}>{row.character_name}</a>
                                       },
                                       {
                                           name: 'Date Achieved',
                                           selector: (row: any) => row.date,
                                       },
                                   ]}
                                   dark_table={this.props.dark_table}
                            />
                        </div>
                    : null
                }

            </Fragment>
        )
    }

    render() {
        return (
            <BasicCard>

                {
                    this.state.loading ?
                        <div className='p-4 m-4 mx-auto w-full md:w-1/2'>
                            <ComponentLoading/>
                        </div>
                        : this.renderRankSection()
                }

            </BasicCard>
        )
    }
}
