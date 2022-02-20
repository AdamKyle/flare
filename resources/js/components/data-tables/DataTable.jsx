import React, {Fragment} from 'react';
import DataTable from 'react-data-table-component';
import AlertError from "../game/components/base/alert-error";

export default class DataTable extends React.Component {

  constructor(props) {
    super(props)

    this.state = {
      data: [],
      loading: true,
      errorMessage: null,
    };
  }

  componentDidMount() {
    axios.get(this.props.url).then((result) => {
      this.setState({
        data: result.data,
        loading: false,
      });
    }).catch((err) => {
      this.setState({
        loading: false,
        errorMessage: 'Could not load the component. Something is wrong.'
      });
    });
  }

  render() {
    return (
      <Fragment>
        {
          this.state.loading ?
            <div className="progress loading-progress" style={{position: 'relative'}}>
              <div className="progress-bar progress-bar-striped indeterminate">
              </div>
            </div>
          : null
        }
        {
          this.state.errorMessage !== null ?
            <AlertError icon={"fas fa-exclamation"} title={'Oops!'}>

            </AlertError>
          : null
        }
        <DataTable
          columns={this.props.columns}
          data={this.state.data}
          pagination
        />
      </Fragment>
    )
  }
}
