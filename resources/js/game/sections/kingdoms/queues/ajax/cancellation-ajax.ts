import {inject, injectable} from "tsyringe";
import Ajax from "../../../../lib/ajax/ajax";
import KingdomQueues from "../kingdom-queues";
import {CancellationType} from "../enums/cancellation-type";
import {BaseQueue} from "../types/kingdom-queue-state";
import {AxiosError, AxiosResponse} from "axios";
import AjaxInterface from "../../../../lib/ajax/ajax-interface";

@injectable()
export default class CancellationAjax {

    constructor(@inject(Ajax) private ajax: AjaxInterface) {}

    public doAjaxCall(component: KingdomQueues, cancellationType: CancellationType, queueData: BaseQueue, characterId: number): void {
        const route = this.getRoute(cancellationType, queueData, characterId);

        this.ajax.setRoute(route).setParameters(
            this.getParameters(cancellationType, queueData)
        ).doAjaxCall('post', (result: AxiosResponse) => {
            component.setState({
                loading: false,
                success_message: result.data.message,
            })
        }, (error: AxiosError) => {
            component.setState({
                loading: false
            });

            if (typeof error.response !== 'undefined') {
                component.setState({
                    error_message: error.response.data.message,
                })
            }
        });
    }

    protected getRoute(cancellationType: CancellationType, queueData: BaseQueue, characterId: number): string {
        switch(cancellationType) {
            case CancellationType.BUILDING_IN_QUEUE:
                return 'kingdoms/building-upgrade/cancel';
            case CancellationType.BUILDING_EXPANSION:
                return 'kingdom/building-expansion/cancel-expand/'+queueData.id+'/' + characterId;
            case CancellationType.UNIT_MOVEMENT:
                return 'recall-units/'+queueData.id+'/' + characterId;
            case CancellationType.UNIT_RECRUITMENT:
                return 'kingdoms/recruit-units/cancel';
            default:
                throw Error('Cannot create route.');

        }
    }

    protected getParameters(cancellationType: CancellationType, queueData: BaseQueue): { queueId: number } | { } {
        switch(cancellationType) {
            case CancellationType.BUILDING_IN_QUEUE:
                return {queue_id: queueData.id}
            case CancellationType.UNIT_RECRUITMENT:
                return {queue_id: queueData.id}
            default:
                return {}
        }
    }
}
