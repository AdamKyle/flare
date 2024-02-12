import React from "react";
import LabyrinthOracleProps from "./types/labyrinth-oracle-props";
import LoadingProgressBar from "../../../../components/ui/progress-bars/loading-progress-bar";
import Select from "react-select";
import LabyrinthOracleState, {LabyrinthOracleInventory} from "./types/labyrinth-oracle-state";
import {formatNumber} from "../../../../lib/game/format-number";
import DangerAlert from "../../../../components/ui/alerts/simple-alerts/danger-alert";
import PrimaryButton from "../../../../components/ui/buttons/primary-button";
import DangerButton from "../../../../components/ui/buttons/danger-button";
import SuccessAlert from "../../../../components/ui/alerts/simple-alerts/success-alert";
import {serviceContainer} from "../../../../lib/containers/core-container";
import Ajax from "../../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";

export default class LabyrinthOracle extends React.Component<LabyrinthOracleProps, LabyrinthOracleState> {

    private ajax: Ajax;

    constructor(props: LabyrinthOracleProps) {
        super(props);

        this.state = {
            loading: true,
            transferring: false,
            item_to_transfer_from: null,
            item_to_transfer_to: null,
            inventory: [],
            error_message: null,
            success_message: null,
        }

        this.ajax = serviceContainer().fetch(Ajax);
    }

    componentDidMount() {
        this.ajax.setRoute('character/'+this.props.character_id+'/labyrinth-oracle')
            .doAjaxCall('get', (result: AxiosResponse) => {
                this.setState({
                    loading: false,
                    inventory: result.data.inventory,
                })
            }, (error: AxiosError) => {
                this.setState({loading: false});

                if (typeof error.response !== 'undefined') {
                    this.setState({
                        error_message: error.response.data.message,
                    });
                }
            })
    }

    transferItems(): {label: string, value: string}[]|[] {
        return this.state.inventory.map((inventoryItem: LabyrinthOracleInventory) => {
            return {
                label: inventoryItem.affix_name,
                value: `${inventoryItem.id}`,
            }
        });
    }

    setSelectedTransferFrom(data: any) {

        if (data.value === '') {
            return;
        }

        this.setState({
            item_to_transfer_from: data.value
        })
    }

    setSelectedTransferTo(data: any) {

        if (data.value === '') {
            return;
        }

        this.setState({
            item_to_transfer_to: data.value
        })
    }

    selectedTransfer(key: string) {

        const isFrom = key === 'item_to_transfer_from';

        if (this.state[key] === null) {
            return {label: 'Please select transfer ' + (isFrom ? 'from' : 'to'), value: ''};
        }

        const foundSelectedItem = this.state.inventory.filter((item: LabyrinthOracleInventory) => {
            return item.id === parseInt(this.state[key])
        });

        if (foundSelectedItem.length === 0) {
            return {label: 'Please select transfer '  + (isFrom ? 'from' : 'to'), value: ''};
        }

        return {
            label: foundSelectedItem[0].affix_name,
            value: foundSelectedItem[0].id,
        }
    }

    transfer() {
        this.setState({
            success_message: null,
            error_message: null,
            transferring: true,
        }, () => {

            this.ajax.setRoute('character/'+this.props.character_id+'/transfer-attributes').setParameters({
                item_id_from: this.state.item_to_transfer_from,
                item_id_to: this.state.item_to_transfer_to,
            }).doAjaxCall('post', (result: AxiosResponse) => {
                this.setState({
                    transferring: false,
                    inventory:result.data.inventory,
                    success_message: result.data.message,
                    item_to_transfer_from: null,
                    item_to_transfer_to: null,
                })
            }, (error: AxiosError) => {
                this.setState({transferring: false});

                if (typeof error.response !== 'undefined') {
                    this.setState({
                        error_message: error.response.data.message,
                    });
                }
            });
        })
    }

    render() {
        return (
          <>
              <div className='mt-2 lg:grid lg:grid-cols-3 lg:gap-2 lg:ml-[120px]'>
                  <div className='lg:cols-start-1 lg:col-span-2'>
                      {
                          this.state.loading && this.state.inventory.length === 0 ?
                              <LoadingProgressBar />
                          :
                              <>
                                  <div className='my-2'>
                                      <Select
                                          onChange={this.setSelectedTransferFrom.bind(this)}
                                          options={this.transferItems()}
                                          menuPosition={'absolute'}
                                          menuPlacement={'bottom'}
                                          styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                                          menuPortalTarget={document.body}
                                          value={this.selectedTransfer('item_to_transfer_from')}
                                      />
                                  </div>
                                  <Select
                                      onChange={this.setSelectedTransferTo.bind(this)}
                                      options={this.transferItems()}
                                      menuPosition={'absolute'}
                                      menuPlacement={'bottom'}
                                      styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                                      menuPortalTarget={document.body}
                                      value={this.selectedTransfer('item_to_transfer_to')}
                                  />

                                  {
                                      this.state.item_to_transfer_from !== null && this.state.item_to_transfer_to !== null ?
                                          <div className='mt-4 mb-2'>
                                              <dl>
                                                  <dt>Gold Cost</dt>
                                                  <dl>{formatNumber(100_000_000)}</dl>
                                                  <dt>Shards Cost</dt>
                                                  <dl>{formatNumber(5_000)}</dl>
                                                  <dt>Gold Dust Cost</dt>
                                                  <dl>{formatNumber(2_500)}</dl>
                                              </dl>
                                          </div>
                                      : null
                                  }

                                  {
                                      this.state.transferring ?
                                          <LoadingProgressBar />
                                      : null
                                  }
                              </>
                      }
                  </div>
              </div>

              {
                  this.state.error_message !== null ?
                      <div className='mt-2 lg:grid lg:grid-cols-3 lg:gap-2 lg:ml-[120px]'>
                          <div className='lg:cols-start-1 lg:col-span-2'>
                              <DangerAlert>
                                  {this.state.error_message}
                              </DangerAlert>
                          </div>
                      </div>
                  : null
              }

              {
                  this.state.success_message !== null ?
                      <div className='mt-2 lg:grid lg:grid-cols-3 lg:gap-2 lg:ml-[120px]'>
                          <div className='lg:cols-start-1 lg:col-span-2'>
                              <SuccessAlert>
                                  {this.state.success_message}
                              </SuccessAlert>
                          </div>
                      </div>
                  : null
              }

              <div className='text-center lg:ml-[-100px] mt-3 mb-3'>
                  <PrimaryButton button_label={'Transfer'}
                                 on_click={this.transfer.bind(this)}
                                 disabled={this.state.loading ||
                                     this.state.item_to_transfer_from === null ||
                                     this.state.item_to_transfer_to === null ||
                                     this.props.cannot_craft ||
                                     this.state.is_transfering
                                }
                  />
                  <DangerButton button_label={'Close'}
                                on_click={this.props.remove_crafting}
                                additional_css={'ml-2'}
                                disabled={this.state.loading || this.props.cannot_craft || this.state.is_transfering} />
                  <a href='/information/labyrinth-oracle' target='_blank' className='relative top-[20px] md:top-[0px] ml-2'>Help <i
                      className="fas fa-external-link-alt"></i></a>
              </div>
          </>
        );
    }
}
