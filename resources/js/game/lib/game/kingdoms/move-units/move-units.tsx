import KingdomReinforcementType from "../types/kingdom-reinforcement-type";
import React, {Fragment} from "react";
import SelectedUnitsToCallType from "../types/selected-units-to-call-type";
import UnitReinforcementType from "../types/unit-reinforcement-type";
import {formatNumber} from "../../format-number";
import Select from "react-select";

type setUnitAmount = (kingdomId: number, unitId: number, unitAmount: number, e: React.ChangeEvent<HTMLInputElement>) => void;

export default class MoveUnits {

    getKingdomSelectionOptions(kingdoms: KingdomReinforcementType[]|[]) {
        return kingdoms.map((kingdom: KingdomReinforcementType) => {
            return {
                label: kingdom.kingdom_name,
                value: kingdom.kingdom_id.toString(),
            }
        });
    }

    setAmountToMove(selectedUnits: SelectedUnitsToCallType[]|[], kingdomId: number, unitId: number, unitAmount: number, e: React.ChangeEvent<HTMLInputElement>) {
        let unitsToCall = JSON.parse(JSON.stringify(selectedUnits));

        const index = unitsToCall.findIndex((unitToCall: SelectedUnitsToCallType) => {
            return unitToCall.kingdom_id === kingdomId && unitToCall.unit_id === unitId;
        });

        let amount: number = parseInt(e.target.value, 10) || 0;

        if (amount <= 0) {
            amount = 0;
        }

        if (amount > unitAmount) {
            amount = unitAmount;
        }

        if (index === -1) {
            if (amount === 0) {
                return;
            }

            unitsToCall.push({
                kingdom_id: kingdomId,
                unit_id:    unitId,
                amount:     amount > unitAmount ? unitAmount : amount,
            });
        } else {
            if (amount === 0) {
                unitsToCall.splice(index, 1);
            }

            unitsToCall[index].amount = amount > unitAmount ? unitAmount : amount;
        }

        return unitsToCall;
    }

    getValueOfUnitsToCall(selectedUnits: SelectedUnitsToCallType[]|[], kingdomId: number, unitId: number): string|number {
        let unitsToCall = JSON.parse(JSON.stringify(selectedUnits));

        const index = unitsToCall.findIndex((unitToCall: SelectedUnitsToCallType) => {
            return unitToCall.kingdom_id === kingdomId && unitToCall.unit_id === unitId
        });

        if (index === -1) {
            return '';
        }

        return unitsToCall[index].amount
    }

    getUnitOptions(kingdoms: KingdomReinforcementType[], selectedUnits: SelectedUnitsToCallType[]|[], selectedKingdoms: number[], setAmountToMove: setUnitAmount) {
        const kingdomsWithUnits = kingdoms.filter((kingdom: KingdomReinforcementType) => {
            if (selectedKingdoms.includes(kingdom.kingdom_id)) {
                return kingdom
            }
        });

        const self = this;

        const units = kingdomsWithUnits.map((kingdom: KingdomReinforcementType, kingdomIndex: number) => {
            return kingdom.units.map((unit: UnitReinforcementType, index: number) => {
                return (
                    <div key={kingdom.kingdom_id + '-' + unit.id}>
                        {
                            index === 0 ?
                                <p className='my-2'>From Kingdom: {kingdom.kingdom_name} and will take: {self.getTimeToTravel(kingdom.time)} to get to this kingdom</p>
                                : null
                        }
                        <div className='flex items-center my-4'>
                            <label className='w-1/2'>{unit.name} Amount to move</label>
                            <div className='w-1/2'>
                                <input type='number'
                                       value={self.getValueOfUnitsToCall(selectedUnits, kingdom.kingdom_id, unit.id)}
                                       onChange={(e: any) => setAmountToMove(kingdom.kingdom_id, unit.id, unit.amount, e)}
                                       className='form-control'
                                />
                                <span className='text-gray-500 dark:text-white text-xs'>Max amount to recruit: {formatNumber(unit.amount)}</span>
                            </div>
                        </div>
                        {
                            kingdom.units.length === index + 1 && kingdomsWithUnits.length > 1 && kingdomsWithUnits.length !== kingdomIndex + 1  ?
                                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                                : null
                        }
                    </div>
                )
            });
        });

        return units;
    }


    renderKingdomSelect(kingdoms: KingdomReinforcementType[]|[], selectedKingdoms: number[]|[], setKingdoms: (data: any) => void) {
        return (
            <Fragment>
                <Select
                    onChange={setKingdoms}
                    isMulti
                    options={this.getKingdomSelectionOptions(kingdoms)}
                    menuPosition={'absolute'}
                    menuPlacement={'bottom'}
                    styles={{menuPortal: (base: any) => ({...base, zIndex: 9999, color: '#000000'})}}
                    menuPortalTarget={document.body}
                    value={this.getSelectedKingdomsValue(kingdoms, selectedKingdoms)}
                />
            </Fragment>

        )
    }

    getSelectedKingdomsValue(kingdoms: KingdomReinforcementType[]|[], selectedKingdoms: number[]|[]) {
        const foundKingdoms = selectedKingdoms.map((kingdom: number) => {
            const index = kingdoms.findIndex((kingdomData: KingdomReinforcementType) => {
                return kingdomData.kingdom_id === kingdom;
            });

            if (index !== -1) {
                return {
                    label: kingdoms[index].kingdom_name,
                    value: kingdoms[index].kingdom_id.toString()
                }
            }
        });

        if (foundKingdoms.length > 0) {
            return foundKingdoms;
        }

        return [{label: 'Please select one or more kingdoms', value: 'Please select one or more kingdoms'}];
    }

    private getTimeToTravel(time: number): string {
        const hours = time / 60;

        if (hours >= 1) {
            return ' roughly ' + hours.toFixed(0) + ' hour(s) ';
        }

        return time + ' minute(s) ';
    }
}
