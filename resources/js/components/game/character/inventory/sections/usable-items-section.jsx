import React, {Fragment} from 'react';
import ItemName from "../../../../marketboard/components/item-name";
import {Alert, Card} from "react-bootstrap";
import ReactDatatable from "@ashvin27/react-datatable";
import UseManyItemsModal from "../modals/use-many-items-modal";
import DestroyUsableModal from "../modals/destroy-usable-modal";
import DestroyAllUsableItemsModal from "../modals/destroy-all-usable-items-modal";

export default class UsableItemsSection extends React.Component {

  constructor(props) {
    super(props);

    this.usable_config = {
      key_column: 'id',
      page_size: 10,
      length_menu: [10, 25, 50, 75],
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
          <button className="btn btn-primary mb-2 mr-2 float-left" onClick={() => this.manageUseItem(row)} disabled={this.state.loading || this.canUseItem(row)}>Use</button>
          <button className="btn btn-danger" onClick={() => this.manageDestroy(row)} disabled={this.state.loading}>Destroy</button>
        </Fragment>
      },
    ];

    this.state = {
      successMessage: null,
      errorMessage: null,
      itemToDestroy: null,
      showUseMany: false,
      showDestroyItem: false,
      showDestroyAll: false,
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

  hasUsableItems() {
    return this.props.usableItems.filter((ui) => ui.item.usable).length > 0
  }

  getSlotId(itemId) {
    const foundItem = this.props.usableItems.filter((ui) => ui.item_id === itemId);

    if (foundItem.length > 0) {
      return foundItem[0].id;
    }

    return null;
  }

  manageUseMany() {
    this.setState({
      showUseMany: !this.state.showUseMany,
    })
  }

  manageDestroy(item) {
    this.setState({
      showDestroyItem: !this.state.showDestroyItem,
      itemToDestroy: item
    })
  }

  manageDestroyAll() {
    this.setState({
      showDestroyAll: !this.state.showDestroyAll,
    })
  }

  canUseItem(item) {
    return item.damages_kingdoms || item.can_use_on_other_items;
  }

  manageUseItem(item) {

    this.setState({
      loading: true,
      errorMessage: null,
      successMessage: null,
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
                  disabled={!this.hasUsableItems()}
                  onClick={this.manageUseMany.bind(this)}
          >
            Use Many
          </button>
          <button className='btn btn-danger mr-2 mt-2'
                  disabled={this.props.usableItems.length === 0}
                  onClick={this.manageDestroyAll.bind(this)}
          >
            Destroy All
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
          {
            this.state.showDestroyItem ?
              <DestroyUsableModal
                characterId={this.props.characterId}
                item={this.state.itemToDestroy}
                setSuccessMessage={this.setSuccessMessage.bind(this)}
                open={this.state.showDestroyItem}
                close={this.manageDestroy.bind(this)}
                getSlotId={this.getSlotId.bind(this)}
              />
              : null
          }
          {
            this.state.showDestroyAll ?
              <DestroyAllUsableItemsModal
                characterId={this.props.characterId}
                setSuccessMessage={this.setSuccessMessage.bind(this)}
                open={this.state.showDestroyAll}
                close={this.manageDestroyAll.bind(this)}
              />
            : null
          }
        </Card.Body>
      </Card>
    )
  }
}
