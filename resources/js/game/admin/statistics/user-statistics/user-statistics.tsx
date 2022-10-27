import React, {Fragment} from "react";
import LoginStatistics from "./components/login-statistics";
import BasicCard from "../../../components/ui/cards/basic-card";
import RegistrationStatistics from "./components/registration-statistics";
import CharacterGoldStatistics from "./components/character-gold-statistics";
import OtherStatistics from "./components/other-statistics";

export default class UserStatistics extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    render() {
        return  (
            <div className='pb-10'>
                <div className='grid lg:grid-cols-2 gap-3 mb-5'>
                    <BasicCard>
                        <h3 className='mb-4'>Logins (Last 7 Days)</h3>
                        <LoginStatistics />
                    </BasicCard>
                    <BasicCard>
                        <h3 className='mb-4'>Registrations (Last 7 Days)</h3>
                        <RegistrationStatistics />
                    </BasicCard>
                </div>
                <BasicCard additionalClasses={'mb-5'}>
                    <h3 className='mb-4'>Character Gold (Over 1 Billion)</h3>
                    <CharacterGoldStatistics />
                </BasicCard>
                <OtherStatistics />
            </div>
        );
    }

}