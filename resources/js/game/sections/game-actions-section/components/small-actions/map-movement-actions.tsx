import React, {Fragment} from "react";
import ComponentLoading from "../../../../components/ui/loading/component-loading";

export default class MapMovementActions extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            selected_map_option: null,
        }
    }

    componentDidMount() {
    }

    render() {
        return  (
            <Fragment>
                {
                    this.state.loading ?
                        <div className='p-5 mb-2'>
                            <ComponentLoading />
                        </div>
                    :
                        <p>Hello world</p>
                }
            </Fragment>
        )
    }
}
