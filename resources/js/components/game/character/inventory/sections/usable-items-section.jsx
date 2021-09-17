import React, {Fragment} from 'react';
import ItemName from "../../../../marketboard/components/item-name";
import {Alert, Card} from "react-bootstrap";
import ReactDatatable from "@ashvin27/react-datatable";
import EquippedSectionDropDowns from "./equipped-section-drop-downs";
import UseManyItemsModal from "../modals/use-many-items-modal";

export default class UsableItemsSection extends React.Component {

  constructor(props) {
    super(props);

    this.usable_config = {
      page_size: 25,
      length_menu: [25, 50, 75],
      show_pagination: true,
      pagination: 'advance',
      hideSizePerPage: true,
    }

    this.usable_headers = [
      {
        key: "affix_name",
        text: "Name",
        sortable: true,
        cell: row => <div>
          <a href={'/items/' + row.id} target="_blank">
            <ItemName item={row} useAffixName={true} />
          </a>
        </div>
      },
      {
        key: 'description',
        text: 'Description',
      },
      {
        name: "actions",
        text: "Actions",
        cell: row => <Fragment>
          <button className="btn btn-primary" onClick={() => this.manageUseItem(row)} disabled={this.state.loading}>Use</button>
        </Fragment>
      },
    ];

    this.state = {
      successMessage: null,
      errorMessage: null,
      showUseMany: false,
      loading: false,
    }
  }

  clearSuccessMessage() {
    this.setState({
      successMessage: null,
    })
  }

  clearErrorMessage() {
    this.setState({
      errorMessage: null,
    })
  }

  setSuccessMessage(message) {
    this.setState({
      successMessage: message,
    })
  }

  setErrorMessage(message) {
    this.setState({
      errorMessage: message,
    })
  }

  formatDataForTable() {
    return this.props.usableItems.map((ui) => ui.item);
  }

  manageUseMany() {
    this.setState({
      showUseMany: !this.state.showUseMany,
    })
  }

  manageUseItem(item) {
    this.setState({
      loading: true
    }, () => {
      axios.post('/api/character/'+this.props.characterId+'/inventory/use-item/' + item.id)
        .then((result) => {
          this.setState({loading: false});
          this.setSuccessMessage(result.data.message);
        }).catch((error) => {
          this.setState({loading: false});
          if (error.hasOwnProperty('response')) {
            const response = error.response;

            if (response.status === 401) {
              return location.reload()
            }

            if (response.status === 429) {
              return window.location.replace('/game');
            }

            if (response.data.hasOwnProperty('message')) {
              this.setErrorMessage(response.data.message)
            }

            if (response.data.hasOwnProperty('error')) {
              this.setErrorMessage(response.data.error)
            }
          }
        });
      });
  }

  render() {
    return(
      <Card>
        <Card.Body>
          {
            this.state.successMessage !== null ?
              <div className="mb-3">
                <Alert variant="success" onClose={this.clearSuccessMessage.bind(this)} dismissible>
                  {this.state.successMessage}
                </Alert>
                <hr />
              </div>
              : null
          }
          {
            this.state.errorMessage !== null ?
              <div className="mb-3">
                <Alert variant="danger" onClose={this.clearErrorMessage.bind(this)} dismissible>
                  {this.state.errorMessage}
                </Alert>
                <hr />
              </div>
              : null
          }
          <button className='btn btn-primary mr-2 mt-2'
                  disabled={this.props.usableItems.length === 0}
                  onClick={this.manageUseMany.bind(this)}
          >
            Use Many
          </button>
          <hr />
          {
            this.state.loading ?
              <div className="progress loading-progress mt-2 mb-2" style={{position: 'relative'}}>
                <div className="progress-bar progress-bar-striped indeterminate">
                </div>
              </div>
              : null
          }
          <ReactDatatable
            config={this.usable_config}
            records={this.formatDataForTable()}
            columns={this.usable_headers}
          />
          {
            this.state.showUseMany ?
              <UseManyItemsModal
                characterId={this.props.characterId}
                usableItems={this.props.usableItems}
                setSuccessMessage={this.setSuccessMessage.bind(this)}
                open={this.state.showUseMany}
                close={this.manageUseMany.bind(this)}
              />
            : null
          }
        </Card.Body>
      </Card>
    )
  }
}