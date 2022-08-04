import React, {Fragment} from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import {AxiosError, AxiosResponse} from "axios";
import Ajax from "../../../../lib/ajax/ajax";
import ComponentLoading from "../../../../components/ui/loading/component-loading";
import Select from "react-select";
import {formatNumber} from "../../../../lib/game/format-number";
import DangerAlert from "../../../../components/ui/alerts/simple-alerts/danger-alert";
import LoadingProgressBar from "../../../../components/ui/progress-bars/loading-progress-bar";
import ConjureModalProps from "../../../../lib/game/types/map/modals/conjure-modal-props";
import ConjureModalState from "../../../../lib/game/types/map/modals/conjure-modal-state";
import CelestialType from "../../../../lib/game/types/actions/monster/celestial-type";


export default class Conjuration extends React.Component<ConjureModalProps, ConjureModalState> {

    constructor(props: ConjureModalProps) {
        super(props);

        this.state = {
            loading: true,
            celestials: [],
            selected_celestial: null,
            error_message: '',
            conjuring: false,
        }
    }

    componentDidMount() {
        (new Ajax()).setRoute('celestial-beings/' + this.props.character_id)
                    .doAjaxCall('get', (response: AxiosResponse) => {
                        this.setState({
                            loading: false,
                            celestials: response.data.celestial_monsters,
                        });
                    }, (error: AxiosError) => {});
    }

    conjure(privateConjure: boolean) {
        if (this.state.selected_celestial === null) {
            return this.setState({
                error_message: 'Select a creature child, before doing that.'
            });
        }

        this.setState({
            conjuring: true,
        }, () => {
            (new Ajax()).setRoute('conjure/' + this.props.character_id).setParameters({
                monster_id: this.state.selected_celestial,
                type: privateConjure ? 'private' : 'public',
            }).doAjaxCall('post', (result: AxiosResponse) => {
                this.setState({
                    conjuring: false,
                }, () => {
                    this.props.handle_close();
                });
            })
        })

    }

    setSelectedCelestial(data: any) {
        this.setState({
            selected_celestial: data.value,
        });
    }

    buildCelestialOptions(): {label: string, value: number}[]|[] {
        return this.state.celestials.map((celestial: CelestialType) => {
            return {
                label: celestial.name + ', Gold Cost: ' + formatNumber(celestial.gold_cost) + ' Gold Dust Cost: ' + formatNumber(celestial.gold_dust_cost),
                value: celestial.id,
            }
        });
    }

    getSelectedCelestial(): {label: string, value: number} {

        const selectedCelestial = this.state.celestials.filter((celestial: CelestialType) =>  {
            if (this.state.selected_celestial !== null) {
                return celestial.id === this.state.selected_celestial.id
            }
        });

        if (selectedCelestial.length > 0) {
            const celestial = selectedCelestial[0];

            return {
                label: celestial.name + ', Gold Cost: ' + formatNumber(celestial.gold_cost) + ' and Gold Dust Cost: ' + formatNumber(celestial.gold_dust_cost),
                value: celestial.id,
            }
        }

        return {
            label: "Please select celestial to conjure",
            value: 0,
        }
    }

    render() {

        return (
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.handle_close}
                      title={this.props.title}
                      secondary_actions={{
                          handle_action: () => this.conjure(false),
                          secondary_button_disabled: this.state.selected_celestial === null,
                          secondary_button_label: 'Conjure',
                      }}
                      tertiary_actions={{
                          handle_action: () => this.conjure(true),
                          tertiary_button_label: 'Private Conjure',
                          tertiary_button_disabled: this.state.selected_celestial === null,
                      }}
            >
                {
                    this.state.loading ?
                        <div className={'h-40'}>
                            <ComponentLoading />
                        </div>
                    :
                        this.state.celestials.length === 0 ?
                            <Fragment>
                                <p>Sorry Child this plane has no celestials for you to conjure.</p>
                            </Fragment>
                        :
                            <Fragment>
                                {
                                    this.state.error_message !== '' ?
                                        <DangerAlert>
                                            {this.state.error_message}
                                        </DangerAlert>
                                    : null
                                }
                                <p className='mb-4'>
                                    For more info, see: <a href='/information/celestials' target='_blank'>Celestials help docs. <i
                                    className="fas fa-external-link-alt"></i></a>
                                </p>
                                <p className='mb-4'>
                                    Check server message section below for relevant details including location. Private conjurations
                                    will show you the location in server messages, public will show everyone as a global message.
                                    Celestials are first come first serve entities.
                                </p>
                                <div className='flex items-center'>
                                    <label className='w-[100px]'>Celestials</label>
                                    <div className='w-2/3'>
                                        <Select
                                            onChange={this.setSelectedCelestial.bind(this)}
                                            options={this.buildCelestialOptions()}
                                            menuPosition={'absolute'}
                                            menuPlacement={'bottom'}
                                            styles={{ menuPortal: (base) => ({ ...base, zIndex: 9999, color: '#000000' }) }}
                                            menuPortalTarget={document.body}
                                            value={this.getSelectedCelestial()}
                                        />
                                    </div>
                                </div>
                                {
                                    this.state.conjuring ?
                                        <LoadingProgressBar />
                                    : null
                                }
                            </Fragment>
                }

            </Dialogue>
        )
    }
}
