
import React, { Fragment } from "react";
import TimerProgressBar from "../../ui/progress-bars/timer-progress-bar";
import UnitMovementDetails from "../queues/deffinitions/unit-movement-details";

export const BuildUnitsInMovementColumns = (cancelUnitMovement: (queueId: number) => void,
                                            unitsInMovement: UnitMovementDetails[]|[]) => {


    return [
        {
            name: 'From Kingdom',
            selector: (row: UnitMovementDetails) => row.from_kingdom_name,
        },
        {
            name: 'To Kingdom',
            selector: (row: UnitMovementDetails) => row.to_kingdom_name,
        },
        {
            name: 'Reason',
            selector: (row: UnitMovementDetails) => row.reason,
        },
        {
            name: 'Time till arrival',
            cell: (row: UnitMovementDetails) => <Fragment>
                <div className='w-full mt-4'>
                    <TimerProgressBar time_remaining={row.time_left} time_out_label={''} additional_css={
                        row.is_recalled || row.is_returning ? 'mt-[-35px]' : ''
                    }/>
                    {
                        row.time_left > 0 && !row.is_returning && !row.is_recalled ?
                            <div className='mt-2 mb-4'>
                                <button className={
                                    'hover:text-red-500 text-red-700 dark:text-red-500 dark:hover:text-red-400 ' +
                                    'disabled:text-red-400 dark:disabled:bg-red-400 disabled:line-through ' +
                                    'focus:outline-none focus-visible:ring-2 focus-visible:ring-red-200 dark:focus-visible:ring-white ' +
                                    'focus-visible:ring-opacity-75'
                                } onClick={() => cancelUnitMovement(row.id)}>Cancel</button>
                            </div>
                        : null
                    }
                </div>
            </Fragment>,
            omit: unitsInMovement.length === 0
        }
    ];
}
