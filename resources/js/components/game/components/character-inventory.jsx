import React          from 'react';
import BootstrapTable from 'react-bootstrap-table-next';

import 'react-bootstrap-table-next/dist/react-bootstrap-table2.min.css'

export default class CharacterInventory extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    const inventory = this.props.inventory;

    const columns   = [{
      dataField: 'name',
      text: 'Item Name'
    }];

    return (
      <div>
        <BootstrapTable keyField='id' data={ inventory.items } columns={ columns } />
      </div>
    );
  }
}
