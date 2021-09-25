import React, {Fragment} from "react";
import ItemName from "../../../../marketboard/components/item-name";
import ReactDatatable from "@ashvin27/react-datatable";
import {Alert} from "react-bootstrap";

export default class SetTabSection extends React.Component {

  constructor(props) {
    super(props);

    this.set_config = {
      key_column: 'id',
      page_size: 10,
      length_menu: [10, 25, 50, 75],
      show_pagination: true,
      pagination: 'advance',
      hideSizePerPage: true,
    }

    this.set_headers = [
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
        key: "type",
        text: "Type",
        sortable: true,
      },
      {
        key: 'base_damages',
        text: 'Base Damage',
        sortable: true,
        cell: row => <div>{row.base_damage !== null ? row.base_damage : 0}</div>
      },
      {
        key: 'base_ac',
        text: 'Base AC',
        sortable: true,
        cell: row => <div>{row.base_ac !== null ? row.base_ac : 0}</div>
      },
      {
        key: 'base_healing',
        text: 'Base Healing',
        sortable: true,
        cell: row => <div>{row.base_healing !== null ? row.base_healing : 0}</div>
      },
      {
        name: "actions",
        text: "Actions",
        cell: row => <Fragment>
          <button
            className="btn btn-primary"
            disabled={this.props.set.is_equipped || this.state.loading}
            onClick={() => this.removeItem(row)}
          >
            Remove
          </button>
        </Fragment>
      },
    ];

    this.state = {
      successMessage: null,
      errorMessage: null,
      loading: false,
    }
  }

  formatDataForTable() {
    return this.props.set.slots.map((s) => s.item);
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

  fetchSlotId(itemId) {
    const found = this.props.set.slots.filter((s) => s.item.id === itemId);

    if (found.length > 0) {
      return found[0].id;
    }
  }

  removeItem(item) {
    this.setState({loading: true, errorMessage: null, successMessage: null});

    axios.post('/api/character/'+this.props.characterId+'/inventory-set/remove', {
      slot_id: this.fetchSlotId(item.id),
      inventory_set_id: this.props.set.id,
    }).then((result) => {
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
    })
  }

  removeAll() {
    this.setState({loading: true, errorMessage: null, successMessage: null});

    axios.post('/api/character/'+this.props.characterId+'/inventory-set/'+this.props.set.id+'/remove-all')
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
  }

  equipSet() {
    this.setState({loading: true});
    axios.post('/api/character/'+this.props.characterId+'/inventory-set/equip/'+this.props.set.id)
      .then((result) => {
        console.log(result);
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
  }

  render() {
    return (
      <div className="mt-4">
        {
          this.props.set.is_equipped ?
            <div className="alert alert-info mb-3">
              <p>You cannot move items from this set or equip this set because it is already equipped.</p>

              <p>To unequip the the set, head to equipped and click "unequip all".</p>

              <p><strong>Equipping non set items, will replace the whole set with that item. You cannot mix and match.</strong></p>
            </div>
          : null
        }
        {
          !this.props.set.can_be_equipped ?
            <div className="alert alert-warning mb-3">
              <p>
                This set cannot be equipped due to the items in it. Remember a set contains:
                2 Weapons (or 1 Shield, 1 Weapon or 1 Bow), 1 of each piece of armour, 2 spells (either 2 healing or
                2 damage or 1 of each), 2 artifacts and 2 rings. Sets can be incomplete and still be equipped.
              </p>

              <p>
                You can still use sets as a stash tab, which seem to be what you are doing here. Gear in sets do not
                count towards your inventory max and can contain as any items as you please.
              </p>
            </div>
          : null
        }

        {
          this.state.successMessage !== null ?
            <div className="mb-3">
              <Alert variant="success" onClose={this.clearSuccessMessage.bind(this)} dismissible>
                {this.state.successMessage}
              </Alert>
            </div>
            : null
        }

        {
          this.state.errorMessage !== null ?
            <div className="mb-3">
              <Alert variant="danger" onClose={this.clearErrorMessage.bind(this)} dismissible>
                {this.state.errorMessage}
              </Alert>
            </div>
            : null
        }

        <hr />
        <button
          className="btn btn-primary"
          disabled={this.props.set.is_equipped || this.props.set.slots.length === 0 || this.state.loading}
          onClick={this.equipSet.bind(this)}
        >
          Equip Set
        </button>
        <button
          className="btn btn-danger mr-2"
          disabled={this.props.set.is_equipped || this.props.set.slots.length === 0 || this.state.loading}
          onClick={this.removeAll.bind(this)}
        >
          Empty Set
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
          config={this.set_config}
          records={this.formatDataForTable()}
          columns={this.set_headers}
        />
      </div>
    );
  }
}