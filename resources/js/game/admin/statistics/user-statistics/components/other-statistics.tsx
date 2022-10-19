import React, {Fragment} from "react";
import Ajax from "../../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import ComponentLoading from "../../../../components/ui/loading/component-loading";
import {Basic} from "react-organizational-chart/dist/stories/Tree.stories";
import BasicCard from "../../../../components/ui/cards/basic-card";
import {formatNumber} from "../../../../lib/game/format-number";

export default class OtherStatistics extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            data: null,
            loading: true,
        }
    }

    componentDidMount() {
        (new Ajax()).setRoute('admin/site-statistics/other-stats').doAjaxCall('get', (result: AxiosResponse) => {
            this.setState({
                data: result.data,
                loading: false,
            });
        }, (error: AxiosError) => {
            console.error(error);
        })
    }

    renderKingdomHolders() {
        const elements: any = [];

        const characterNames = Object.keys(this.state.data.kingdomHolders);

        characterNames.forEach((characterName) => {
            elements.push(
                <Fragment>
                    <dd>{characterName}</dd>
                    <dt>{this.state.data.kingdomHolders[characterName]}</dt>
                </Fragment>
            );
        });

        return elements;
    }

    render() {

        if (this.state.loading) {
            return <ComponentLoading />
        }

        console.log(this.state.data);

        return (
            <Fragment>
                <BasicCard additionalClasses={'mb-5'}>
                    <h3 className='mb-4'>Login Details</h3>
                    <dl>
                        <dt>Last Login count (5 Months):</dt>
                        <dd>{this.state.data.lastFiveMonthsLoggedInCount}</dd>
                        <dt>Last Login count (Today):</dt>
                        <dd>{this.state.data.lastLoggedInCount}</dd>
                        <dt>Never Logged in count:</dt>
                        <dd>{this.state.data.neverLoggedInCount}</dd>
                        <dt>Accounts to be deleted:</dt>
                        <dd>{this.state.data.willBeDeletedCount}</dd>
                    </dl>
                </BasicCard>
                <div className='grid lg:grid-cols-2 gap-3 mb-5'>
                    <BasicCard>
                        <h3 className='mb-4'>Averages</h3>
                        <dl>
                            <dt>Average Character Level:</dt>
                            <dd>{this.state.data.averageCharacterLevel}</dd>
                            <dt>Average Character Gold:</dt>
                            <dd>{this.state.data.averageCharacterGold}</dd>
                        </dl>
                    </BasicCard>
                    <BasicCard>
                        <h3 className='mb-4'>Highest Level and Richest Character</h3>
                        <dl>
                            <dt>Highest Level Character:</dt>
                            <dd>{this.state.data.highestLevelCharacter.name} (LV: {this.state.data.highestLevelCharacter.level})</dd>
                            <dt>Richest Character:</dt>
                            <dd>{this.state.data.richestCharacter.name} (Gold: {formatNumber(this.state.data.richestCharacter.gold)})</dd>
                        </dl>
                    </BasicCard>
                </div>
                <BasicCard additionalClasses={'mb-5'}>
                    <h3 className='mb-4'>Kingdom Details</h3>
                    <dl>
                        <dt>Total Character Owned Kingdoms:</dt>
                        <dd>{this.state.data.characterKingdomCount}</dd>
                        <dt>Total NPC Kingdoms:</dt>
                        <dd>{this.state.data.npcKingdomCount}</dd>
                    </dl>
                </BasicCard>
                <BasicCard additionalClasses={'mb-5'}>
                    <h3 className='mb-4'>Character Kingdom Count</h3>
                    <dl>
                        {this.renderKingdomHolders()}
                    </dl>
                </BasicCard>
            </Fragment>
        )
    }
}
