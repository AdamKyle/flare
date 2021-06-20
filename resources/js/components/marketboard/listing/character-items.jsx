import React, {Component} from 'react';
import ReactDOM from 'react-dom';
import ReactDatatable from '@ashvin27/react-datatable';
import Card from '../../game/components/templates/card';

export default class CharacterItems extends Component {
  constructor(props) {
    super(props);

    this.columns = [
      {
        key: "name",
        text: "Name",
        sortable: false
      },
      {
        key: "type",
        text: "Item Type",
        sortable: false
      },
    ];

    this.config = {
      page_size: 10,
      length_menu: [10, 25, 50, 100],
      show_filter: true,
      show_pagination: true,
      pagination: 'advance',
    }

    this.state = {
      records: [],
      showModal: false,
      modalData: {},
      is404: false,
    }
  }

  componentDidMount() {
    axios.get('/api/market-board/character-items/' + this.props.characterId)
    .then((result) => {
      this.setState({
        records: result.data.slots,
      });
    }).catch((error) => {
      if (error.hasOwnProperty('response')) {
        const response = error.response;

        if (response.status === 401) {
          return location.reload();
        }

        if (response.status === 429) {
          return window.location = '/game';
        }
      }
    });
  }

  rowClickedHandler(event, data, rowIndex) {
    console.log(data.item.id);

    axios.get('/api/market-board/item', {
      params: {
        item_id: data.item.id
      }
    }).then((result) => {
      this.setState({
        modalData: result.data,
        showModal: true,
      });
    }).catch((err) => {
      if (err.hasOwnProperty('response')) {
        if (err.status === 401) {
          return location.reload();
        }

        if (err.status === 429) {
          return window.location = '/game';
        }
      }
    })
  }

  closeModal() {
    this.setState({
      modalData: {},
      showModal: false,
    });
  }

  itemListedMessage(message, messageType) {
    this.setState({
      message: message,
      messageType: messageType,
    });
  }

  typeChange(type) {
    let params = {};

    if (type !== 'reset') {
      params = {
        params: {
          type: type
        }
      };

      this.setState({
        type: type,
      });
    } else {
      this.setState({
        type: null,
      });
    }


    axios.get('/api/market-board/character-items/' + this.props.characterId, params).then((result) => {
      this.setState({
        records: result.data.slots,
      });
    }).catch((error) => {
      if (error.hasOwnProperty('response')) {
        const response = error.response;

        if (response.status === 401) {
          return location.reload();
        }

        if (response.status === 429) {
          return window.location = '/game';
        }
      }
    });
  }

  render() {
    return (
      <Card
        OtherCss="p-3"
        cardTitle="Sellable Items"
        showButton={true}
        customButtonType="drop-down"
        buttonTitle="Types"
        buttons={[
          {type: 'reset', name: 'Reset Filter'},
          {type: 'weapon', name: 'Weapon'},
          {type: 'body', name: 'Body'},
          {type: 'shield', name: 'Shield'},
          {type: 'feet', name: 'Feet'},
          {type: 'leggings', name: 'Leggings'},
          {type: 'sleeves', name: 'Sleeves'},
          {type: 'helmet', name: 'Helmet'},
          {type: 'gloves', name: 'Gloves'},
          {type: 'spell-damage', name: 'Spell Damage'},
          {type: 'spell-healing', name: 'Spell Healing'},
          {type: 'ring', name: 'Ring'},
        ]}
        onChange={this.typeChange.bind(this)}
      >
        {
          this.state.is404 ?
            <div className="alert alert-danger mb-2 mt-2">
              Item Not found
            </div>
          : null
        }
        <ReactDatatable
          config={this.config}
          records={this.state.records}
          columns={this.columns}
          onRowClicked={this.rowClickedHandler.bind(this)}
        />
      </Card>
    );
  }
}

const characterItems = document.getElementById('sell-items-on-market');
const character = document.head.querySelector('meta[name="character"]');

if (characterItems !== null) {
  ReactDOM.render(
    <CharacterItems characterId={character.content}/>,
    characterItems
  );
}
