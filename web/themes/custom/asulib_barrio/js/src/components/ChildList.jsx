import React, { useEffect, useState } from "react";

function isValidData(data) {
    if (data === null) {
        return false;
    }
    if (data.data === undefined ||
        data.data === null ||
        data.data.length === 0) {
        return false;
    }
    return true;
}

const NodeItem = ({ drupal_internal__nid, title }) => (
    <div class="col col-md-4">
        <a href={`/node/${drupal_internal__nid}`}>{title}</a>
    </div>
);

const NoData = () => (
    <div>No items found.</div>
);

const ChildList = ({node_id}) => {
    const [content, setContent] = useState(false);
    const [filter, setFilter] = useState(null);

    useEffect(() => {

        const API_ROOT = '/jsonapi/';
        // const url = `${API_ROOT}node/article?fields[node--article]=id,drupal_internal__nid,title,body&sort=-created&page[limit]=10`;
        const url = `${API_ROOT}node/asu_repository_item?filter[membership][condition][operator]=%3D&filter[membership][condition][path]=field_member_of.meta.drupal_internal__target_id&filter[membership][condition][value]=${node_id}`;

        const headers = new Headers({
            Accept: 'application/vnd.api+json',
        });

        fetch(url, { headers })
            .then((response) => response.json())
            .then((data) => {
                if (isValidData(data)) {
                    setContent(data.data)
                }
            })
            .catch(err => console.log('There was an error accessing the API', err));
    }, []);

    return (
        <div>
            {content ? (
                <>
                    <label htmlFor="filter">Type to filter:</label>
                    <input
                        type="text"
                        name="filter"
                        placeholder="Start typing ..."
                        onChange={(event => setFilter(event.target.value.toLowerCase()))}
                    />
                    <hr />
                    <div className="row">
                    {
                        content.filter((item) => {
                            console.log(item)
                            if (!filter) {
                                return item;
                            }

                            if (filter && (item.attributes.title.toLowerCase().includes(filter) || item.attributes.body.value.toLowerCase().includes(filter))) {
                                return item;
                            }
                        }).map((item) => <NodeItem key={item.id} {...item.attributes} />)
                    }
                    </div>
                </>
            ) : (
                <NoData />
            )}
        </div>
    );
};

export default ChildList;
