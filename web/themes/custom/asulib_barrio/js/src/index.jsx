import React from 'react';
import ReactDOM from 'react-dom';
import ChildList from "./components/ChildList";


console.log(drupalSettings.reactApp);
//

// # Example 1: Simple "Hello, World" code
// ReactDOM.render(
//     <h1>Hi there. My setting is {drupalSettings.reactApp.node_id}</h1>,
//     document.getElementById('react-app')
// );


const Main = () => (
    <ChildList node_id={drupalSettings.reactApp.node_id} />
);

ReactDOM.render(<Main />, document.getElementById('react-app'));
