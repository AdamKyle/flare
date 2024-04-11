import {inject, injectable} from "tsyringe";
import Ajax from "../../../../game/lib/ajax/ajax.js";
import AjaxInterface from "../../../../game/lib/ajax/ajax-interface.js";
import {TableType} from "../types/table-type";
import Items from "../items";
import {AxiosError, AxiosResponse} from "axios";

@injectable()
export default class ItemTableAjax {

    constructor(@inject(Ajax) private ajax: AjaxInterface) {}


    public fetchTableData(component: Items, type: string | null) {
        if (type === null) {
            component.setState({loading: false});

            return;
        }

        if (type === TableType.CRAFTING) {
            return this.fetchCraftingTableItems(component);
        }

        let specialtyType = null;

        try {
            specialtyType = this.mapTypeToItemType(type);
        } catch (e: any) {
            component.setState({
                loading: false,
                error_message: e.message
            })
        }

        if (specialtyType === null) {
            return;
        }

        this.fetchSpecialtyTypeItems(component, specialtyType);
    }

    private fetchCraftingTableItems(component: Items) {
        this.ajax.setRoute('items-list').doAjaxCall('get', (result: AxiosResponse) => {
            component.setState({
                loading: false,
                items: result.data.items,
            });
        }, (error: AxiosError) => {

            component.setState({
                loading: false,
            })

            if (typeof error.response !== 'undefined') {
                const response = error.response;

                component.setState({
                    error_message: response.data.message,
                })
            }
        })
    }

    private fetchSpecialtyTypeItems(component: Items, specialtyType: string) {
        this.ajax.setRoute('items-list-for-type').setParameters({
            specialty_type: specialtyType,
        }).doAjaxCall('get', (result: AxiosResponse) => {
            component.setState({
                loading: false,
                items: result.data.items,
            });
        }, (error: AxiosError) => {

            component.setState({
                loading: false,
            })

            if (typeof error.response !== 'undefined') {
                const response = error.response;

                component.setState({
                    error_message: response.data.message,
                })
            }
        })
    }

    private mapTypeToItemType(type: string) {

        switch(type) {
            case TableType.HELL_FORGED:
                return 'Hell Forged';
            case TableType.PURGATORY_CHAINS:
                return 'Purgatory Chains';
            case TableType.PIRATE_LORD_LEATHER:
                return 'Pirate Lord Leather';
            case TableType.CORRUPTED_ICE:
                return 'Corrupted Ice';
            case TableType.TWISTED_EARTH:
                return 'Twisted Earth';
            case TableType.DELUSIONAL_SILVER:
                return 'Delusional Silver';
            default:
                throw new Error('Unknown type of table to render.');
        }
    }
}
