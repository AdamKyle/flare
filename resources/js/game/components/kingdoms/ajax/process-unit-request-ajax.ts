import { inject, injectable } from "tsyringe";
import Ajax from "../../../lib/ajax/ajax";
import AjaxInterface from "../../../lib/ajax/ajax-interface";
import { AxiosError, AxiosResponse } from "axios";
import UnitRecruitment from "../capital-city/partials/unit-management/unit-recruitment";

@injectable()
export default class ProcessUnitRequestAjax {
    constructor(@inject(Ajax) private ajax: AjaxInterface) {}

    public processRequest(
        component: UnitRecruitment,
        characterId: number,
        kingdomId: number,
        params: any,
    ): void {
        this.ajax
            .setRoute(
                "kingdom/capital-city/recruit-unit-requests/" +
                    characterId +
                    "/" +
                    kingdomId,
            )
            .setParameters({
                request_data: params,
            })
            .doAjaxCall(
                "post",
                (result: AxiosResponse) => {
                    this.applyAcceptedRequestState(component, params);
                },
                (error: AxiosError) => {
                    component.setState({
                        processing_request: false,
                    });

                    if (typeof error.response !== "undefined") {
                        const response: AxiosResponse = error.response;

                        component.setState({
                            error_message: response.data.message,
                        });
                    }
                },
            );
    }

    private applyAcceptedRequestState(
        component: UnitRecruitment,
        submittedQueue: any[],
    ): void {
        const queuedUnitNamesByKingdom =
            this.queuedUnitNamesByKingdom(submittedQueue);
        const updatedUnitRecruitmentData = this.removeQueuedUnitsFromKingdoms(
            component.state.unit_recruitment_data,
            queuedUnitNamesByKingdom,
        );
        const updatedFilteredUnitRecruitmentData =
            this.removeQueuedUnitsFromKingdoms(
                component.state.filtered_unit_recruitment_data,
                queuedUnitNamesByKingdom,
            );
        const fadingKingdomIds = this.fadingKingdomIds(
            component.state.unit_recruitment_data,
            updatedUnitRecruitmentData,
            queuedUnitNamesByKingdom,
        );
        const bulkInputValues = { ...component.state.bulk_input_values };

        Object.keys(queuedUnitNamesByKingdom).forEach((kingdomId: string) => {
            delete bulkInputValues[kingdomId];
        });

        if (fadingKingdomIds.size <= 0) {
            component.setState({
                processing_request: false,
                unit_recruitment_data: updatedUnitRecruitmentData,
                filtered_unit_recruitment_data:
                    updatedFilteredUnitRecruitmentData,
                unit_queue: this.removeQueuedUnitsFromUnitQueue(
                    component.state.unit_queue,
                    queuedUnitNamesByKingdom,
                ),
                bulk_input_values: bulkInputValues,
                info_message: "Unit recruitment orders were accepted.",
                success_message: null,
                error_message: null,
                current_page: Math.min(
                    component.state.current_page,
                    this.calculateTotalPages(
                        component,
                        updatedFilteredUnitRecruitmentData.length,
                    ),
                ),
            });

            return;
        }

        const nextFadingKingdomIds = new Set(
            component.state.fading_kingdom_ids,
        );

        fadingKingdomIds.forEach((kingdomId: number) => {
            nextFadingKingdomIds.add(kingdomId);
        });

        component.setState({
            processing_request: false,
            unit_recruitment_data: this.keepFadingKingdomsRendered(
                component.state.unit_recruitment_data,
                updatedUnitRecruitmentData,
                fadingKingdomIds,
            ),
            filtered_unit_recruitment_data: this.keepFadingKingdomsRendered(
                component.state.filtered_unit_recruitment_data,
                updatedFilteredUnitRecruitmentData,
                fadingKingdomIds,
            ),
            unit_queue: this.removeQueuedUnitsFromUnitQueue(
                component.state.unit_queue,
                queuedUnitNamesByKingdom,
                fadingKingdomIds,
            ),
            bulk_input_values: bulkInputValues,
            fading_kingdom_ids: nextFadingKingdomIds,
            info_message: "Unit recruitment orders were accepted.",
            success_message: null,
            error_message: null,
        });

        window.setTimeout(() => {
            const currentFadingKingdomIds = new Set(
                component.state.fading_kingdom_ids,
            );

            fadingKingdomIds.forEach((kingdomId: number) => {
                currentFadingKingdomIds.delete(kingdomId);
            });

            component.setState({
                unit_recruitment_data: updatedUnitRecruitmentData,
                filtered_unit_recruitment_data:
                    updatedFilteredUnitRecruitmentData,
                unit_queue: this.removeQueuedUnitsFromUnitQueue(
                    component.state.unit_queue,
                    queuedUnitNamesByKingdom,
                ),
                fading_kingdom_ids: currentFadingKingdomIds,
                current_page: Math.min(
                    component.state.current_page,
                    this.calculateTotalPages(
                        component,
                        updatedFilteredUnitRecruitmentData.length,
                    ),
                ),
            });
        }, 300);
    }

    private queuedUnitNamesByKingdom(submittedQueue: any[]): any {
        const queuedUnitNamesByKingdom: any = {};

        submittedQueue.forEach((kingdomQueue: any) => {
            queuedUnitNamesByKingdom[kingdomQueue.kingdom_id] = (
                kingdomQueue.unit_requests ?? []
            ).map((unitRequest: any) => unitRequest.unit_name);
        });

        return queuedUnitNamesByKingdom;
    }

    private removeQueuedUnitsFromKingdoms(
        kingdoms: any[],
        queuedUnitNamesByKingdom: any,
    ): any[] {
        return kingdoms
            .map((kingdom: any) => {
                const queuedUnitNames = queuedUnitNamesByKingdom[kingdom.id];

                if (!Array.isArray(queuedUnitNames)) {
                    return kingdom;
                }

                const availableUnitTypes = kingdom.available_unit_types.filter(
                    (unitName: string) => !queuedUnitNames.includes(unitName),
                );

                if (availableUnitTypes.length <= 0) {
                    return null;
                }

                return {
                    ...kingdom,
                    available_unit_types: availableUnitTypes,
                };
            })
            .filter((kingdom: any) => kingdom !== null);
    }

    private fadingKingdomIds(
        originalKingdoms: any[],
        updatedKingdoms: any[],
        queuedUnitNamesByKingdom: any,
    ): Set<number> {
        const updatedKingdomIds = new Set(
            updatedKingdoms.map((kingdom: any) => kingdom.id),
        );
        const fadingKingdomIds = new Set<number>();

        originalKingdoms.forEach((kingdom: any) => {
            if (
                Array.isArray(queuedUnitNamesByKingdom[kingdom.id]) &&
                !updatedKingdomIds.has(kingdom.id)
            ) {
                fadingKingdomIds.add(kingdom.id);
            }
        });

        return fadingKingdomIds;
    }

    private keepFadingKingdomsRendered(
        originalKingdoms: any[],
        updatedKingdoms: any[],
        fadingKingdomIds: Set<number>,
    ): any[] {
        const updatedKingdomsById = new Map(
            updatedKingdoms.map((kingdom: any) => [kingdom.id, kingdom]),
        );

        return originalKingdoms
            .map((kingdom: any) => {
                if (fadingKingdomIds.has(kingdom.id)) {
                    return kingdom;
                }

                return updatedKingdomsById.get(kingdom.id) ?? null;
            })
            .filter((kingdom: any) => kingdom !== null);
    }

    private removeQueuedUnitsFromUnitQueue(
        unitQueue: any[],
        queuedUnitNamesByKingdom: any,
        fadingKingdomIds: Set<number> = new Set(),
    ): any[] {
        return unitQueue
            .map((kingdomQueue: any) => {
                if (fadingKingdomIds.has(kingdomQueue.kingdom_id)) {
                    return kingdomQueue;
                }

                const queuedUnitNames =
                    queuedUnitNamesByKingdom[kingdomQueue.kingdom_id];

                if (!Array.isArray(queuedUnitNames)) {
                    return kingdomQueue;
                }

                const unitRequests = kingdomQueue.unit_requests.filter(
                    (unitRequest: any) =>
                        !queuedUnitNames.includes(unitRequest.unit_name),
                );

                if (unitRequests.length <= 0) {
                    return null;
                }

                return {
                    ...kingdomQueue,
                    unit_requests: unitRequests,
                };
            })
            .filter((kingdomQueue: any) => kingdomQueue !== null);
    }

    private calculateTotalPages(
        component: UnitRecruitment,
        totalItems: number,
    ): number {
        return Math.max(
            1,
            Math.ceil(totalItems / component.state.items_per_page),
        );
    }
}
