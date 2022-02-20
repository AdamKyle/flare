import React from 'react';
import DataTable from "../../DataTable";

export default class Skills extends React.Component {

  constructor(props) {
    super(props);

    this.columns = [
      {
        name: 'Name',
        selector: row => row.name
      },
      {
        name: 'Can train',
        selector: row => row.can_train ? 'Yes' : 'No'
      }
    ]
  }

  render() {
    return <DataTable url={'/api/information/skills'} columns={this.columns} />
  }
}
