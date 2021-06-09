import React, {Component} from 'react';
import ReactDOM from 'react-dom';
import ReactDatatable from '@ashvin27/react-datatable';
import Card from '../game/components/templates/card';
import MarketHistory from './market-history';
import PurchaseModal from './purchase-modal';

export default class Board extends Component {
  constructor(props) {
    super(props);

    this.columns = [
      {
        key: "name",
        text: "Name",
        sortable: true
      },
      {
        key: "type",
        text: "Item Type",
        sortable: true
      },
      {
        key: "character_name",
        text: "Listed By",
        sortable: true
      },
      {
        key: "listed_price",
        text: "Listed For",
        sortable: true,
        cell: row => <div data-tag="allowRowEvents">
          <div>{row.listed_price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")}</div>
        </div>,
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
      gold: 0,
      showModal: false,
      modalData: {},
      message: null,
      messageType: null,
      hasItemId: false,
      allowBuying: true,
      type: null,
    }

    this.update = Echo.join('update-market');
  }

  componentDidMount() {
    this.setState({
      allowBuying: this.props.hasOwnProperty('allowBuying') ? this.props.allowBuying : true,
      hasItemId: this.props.hasOwnProperty('itemId') && this.props.itemId !== 'undefined'
    }, () => {
      axios.get('/api/market-board/items', {
        params: {
          item_id: this.props.itemId,
        }
      }).then((result) => {
        console.log(result.data.items);
        this.setState({
          records: result.data.items,
          gold: result.data.gold,
        });
      }).catch((error) => {
        console.error(error);
      });

      if (!this.state.hasItemId) {
        this.update.listen('Game.Core.Events.UpdateMarketBoardBroadcastEvent', (event) => {
          let hasId = false;

          if (!_.isEmpty(this.state.modalData)) {
            hasId = _.isEmpty(event.marketListings.filter((ml) => ml.id === this.state.modalData.id));
          }

          if (this.state.showModal && hasId) {
            this.closeModal();

            this.setState({
              message: 'Sorry, that item was sold.',
              messageType: 'info',
            });
          }

          this.setState({
            records: event.marketListings,
            gold: event.characterGold,
          });
        });
      }
    });
  }

  rowClickedHandler(event, data, rowIndex) {
    if (!this.state.allowBuying) {
      return;
    }

    this.setState({
      modalData: data,
      showModal: true,
    });
  }

  closeModal() {
    this.setState({
      modalData: {},
      showModal: false,
    });
  }

  updateMessage(message, messageType) {
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


    axios.get('/api/market-board/items', params).then((result) => {
      this.setState({
        records: result.data.items,
        gold: result.data.gold,
      });
    }).catch((error) => {
      console.error(error);
    });
  }

  getGoldText() {
    return 'Your Gold: ' + this.state.gold;
  }

  closeMessage() {
    this.setState({
      message: null,
      messageType: null,
    });
  }

  renderMessage() {
    return (
      <div className={"alert alert-" + this.state.messageType} role="alert">
        {this.state.message}
        <button type="button" className="close" onClick={this.closeMessage.bind(this)}>
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
    )
  }

  render() {
    return (
      <Card
        OtherCss="p-3"
        cardTitle="Market Board"
        showButton={!this.state.hasItemId}
        textBesideButton={this.getGoldText()}
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
          {type: 'artifact', name: 'Artifact'},
        ]}
        onChange={this.typeChange.bind(this)}
      >
        {this.state.message !== null ? this.renderMessage() : null}

        {!this.state.hasItemId && this.state.allowBuying ? <MarketHistory type={this.state.type}/> : null}

        <ReactDatatable
          config={this.config}
          records={this.state.records}
          columns={this.columns}
          onRowClicked={this.rowClickedHandler.bind(this)}
        />

        {this.state.showModal ?
          <PurchaseModal
            closeModal={this.closeModal.bind(this)}
            showModal={this.state.showModal}
            modalData={this.state.modalData}
            characterId={this.props.characterId}
            updateMessage={this.updateMessage.bind(this)}
            characterGold={this.state.gold}
          /> : null
        }
      </Card>
    );
  }
}

const marketBoard = document.getElementById('market');
const character = document.head.querySelector('meta[name="character"]');

if (marketBoard !== null) {
  ReactDOM.render(
    <Board characterId={character.content}/>,
    marketBoard
  );
}
