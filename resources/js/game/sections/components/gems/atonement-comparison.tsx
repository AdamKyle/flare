import Dialogue from "../../../components/ui/dialogue/dialogue";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import React, {Fragment} from "react";
import Ajax from "../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import Tabs from "../../../components/ui/tabs/tabs";
import TabPanel from "../../../components/ui/tabs/tab-panel";
import AddingTheGem from "./adding-the-gem";
import ReplacingAGem from "./replacing-a-gem";
import InfoAlert from "../../../components/ui/alerts/simple-alerts/info-alert";
import {formatNumber} from "../../../lib/game/format-number";
import SeerActions from "../../../lib/game/actions/seer-camp/seer-actions";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import ManageGemsState from "./types/manage-gems-state";
import ManageGemsProps from "./types/manage-gems-props";
import {ActionTypes} from "./types/adding-the-gem-props";
import clsx from "clsx";
import BasicCard from "../../../components/ui/cards/basic-card";

export default class AtonementComparison extends React.Component<any, any> {
    constructor(props: any) {
        super(props);
    }

    renderAtonements(atonementData: any): JSX.Element[]|[] {
        return atonementData.map((data: any) => {
            return (
                <Fragment>
                    <dt>{data.name}</dt>
                    <dd>{(data.total * 100).toFixed(2)}%</dd>
                </Fragment>
            )
        })
    }

    renderDifference(atonementData: any, originalAtonement: any): JSX.Element[]|[] {
        return atonementData.atonements.map((data: any) => {

            const atonementValue = this.findElementAtonement(originalAtonement, data.name);

            return (
               <Fragment>
                    <dt>{data.name}</dt>
                    <dd
                        className={clsx({
                            'text-green-700 dark:text-green-500': data.total > atonementValue,
                            'text-red-700 dark:text-red-500': data.total < atonementValue
                        })}
                    >{(data.total * 100).toFixed(2)}%</dd>
                </Fragment>
            );
        });
    }

    findElementAtonement(originalAtonement: any, elementName: string): number {
        const element = originalAtonement.filter((atonement: any) => atonement.name === elementName);

        if (element.length > 0) {
            return element[0].total;
        }

        return 0;
    }

    findAtonementsForReplacing(): any[]|[] {
        return this.props.if_replacing.filter((data: any) => {
            return data.name_to_replace === this.props.gem_name;
        })[0].data
    }

    render() {
        return(
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.manage_model}
                      title={'Replacement details'}
                      primary_button_disabled={this.props.trading_with_seer}
            >
                <p className='my-4'>Below are your Atonement Adjustment Details. Each item can be atoned to a specific element.</p>
                <p className='my-4'>Upon doing so, taking into account your overall gear and the items atonements, your element damage/resistances
                could change.</p>
                <div className='my-4 grid lg:grid-cols-2 gap-2'>
                    <BasicCard>
                        <h3 className='my-4'>Original Atonement</h3>
                        <dl>
                            {this.renderAtonements(this.props.original_atonement.atonements)}
                        </dl>
                    </BasicCard>
                    <BasicCard>
                        <h3 className='my-4'>Adjusted Atonement</h3>
                        <dl>
                            {this.renderDifference(this.findAtonementsForReplacing(), this.props.original_atonement.atonements)}
                        </dl>
                    </BasicCard>
                </div>
            </Dialogue>
        );
    }

}
