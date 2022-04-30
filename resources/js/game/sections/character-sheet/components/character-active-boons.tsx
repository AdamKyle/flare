import React, {Fragment} from "react";
import Ajax from "../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import {watchForDarkModeTableChange} from "../../../lib/game/dark-mode-watcher";
import ComponentLoading from "../../../components/ui/loading/component-loading";
import InfoAlert from "../../../components/ui/alerts/simple-alerts/info-alert";

export default class CharacterActiveBoons extends React.Component<any, any> {

  constructor(props: any) {
    super(props);

    this.state = {
        loading: true,
        boons: [],
        dark_tables: false,
    }
  }

  componentDidMount() {
      if (this.props.character_id !== null) {
          watchForDarkModeTableChange(this);

          (new Ajax()).setRoute('character-sheet/' + this.props.character_id + '/active-boons').doAjaxCall('get', (result: AxiosResponse) => {
              this.setState({
                  loading: false,
                  boons: result.data.active_boons,
              });
          }, (error: AxiosError) => {
              console.log(error);
          })
      }
  }

  render() {
    if (this.state.loading) {
        return (
            <div className="relative top-[20px]">
                <ComponentLoading />
            </div>
        )
    }

    return (
        <Fragment>
            <InfoAlert>
                This tab does not update in real time. You can switch tabs to get the latest data.
            </InfoAlert>
            <p>Content</p>
        </Fragment>
    )
  }
}
