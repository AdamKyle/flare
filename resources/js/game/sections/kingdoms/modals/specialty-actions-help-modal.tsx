import React, {Fragment} from "react";
import Dialogue from "../../../components/ui/dialogue/dialogue";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import BuyPopulationModalProps from "../../../lib/game/kingdoms/types/modals/buy-population-modal-props";
import BuyPopulationModalState from "../../../lib/game/kingdoms/types/modals/buy-population-modal-state";
import { formatNumber } from "../../../lib/game/format-number";
import Ajax from "../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import clsx from "clsx";
import TimerProgressBar from "../../../components/ui/progress-bars/timer-progress-bar";
import DangerButton from "../../../components/ui/buttons/danger-button";

export default class SpecialtyActionsHelpModal extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    render() {
        return(
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.handle_close}
                      title={'Specialty Actions Help'}
            >
               <div className='my-4'>
                   <h3 className='my-3'>Smelting</h3>
                   <p className='mb-2'>
                      Smelting is unlocked by completing a quest line in Hell starting with the quest: Story of the Red Hawks.
                      This will then unlock the Passive: Blacksmith's Furnace, which after leveling it to level one, will
                      then unlock the Blacksmith's Furnace building.
                   </p>

                   <p>
                       Once this building is then level 6, you can use the Smelter to smelt iron into steel which is
                       then used to build the Airship Fields - who in turn let you recruit Airships.
                   </p>
               </div>

                <div className='my-4'>
                    <h3 className='my-3'>Manage Gold Bars</h3>
                    <p className='mb-2'>
                        Gold bars are a way for you to store excess gold in the kingdom treasury. You can do this by unlocking and
                        leveling the Goblin Coin Bank passive, then leveling the building to level 5.
                    </p>

                    <p>
                        Each gold bar costs 1 billion gold. You can store 1000 gold bars for a total of 2 Trillion Gold. This will
                        also increase your kingdom defence by 1% for every ~10  gold bars to a maximum of 100%.
                    </p>
                </div>
            </Dialogue>
        );
    }
}
