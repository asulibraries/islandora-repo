import requests

handle_base = "https://handle.asu.edu:8000/api/handles/2286/"
get_headers = {
    'Authorization': 'Basic ' + basic_auth
}
post_headers = get_headers
post_headers['Content-Type'] = 'application/json'
post_headers['Accept'] = 'application/json'


def get_handle(handle_num, hh):
    get_response = requests.request(
        "GET", handle_base + str(handle_num), headers=get_headers)
    # print(get_response.json())
    get_json = get_response.json()
    hdl_match = False
    if 'values' in get_json:
        for v in get_json['values']:
            if v['data']['value'] == hh['view_node']:
                hdl_match = True
                return True
        if not hdl_match:
            # print("not a match")
            # print("node url is ", hh['view_node'])
            return False
    else:
        # print("no values")
        # print(get_json)
        handle_num = handle_num.replace("R.I", "R.A")
        # try it with R.A.
        return get_handle(handle_num, hh)


keep_handles = "https://keep.lib.asu.edu/handles?_format=json"
i = 0
all_the_handles = []
while True:
    response = requests.request("GET", keep_handles + "&page=" + str(i))
    data = response.json()
    # print(data)
    if len(data) < 1:
        break
    for h in data:
        all_the_handles.append(h)
    i = i + 1

# print(all_the_handles)
print(len(all_the_handles))


for hh in all_the_handles:
    handle_parts = hh['field_handle'].split("/")
    handle_num = handle_parts[-1]
    print(handle_num)
    # print(handle_base + str(handle_num))
    if not get_handle(handle_num, hh):
        # update handle
        url = handle_base + handle_num + "?overwrite=true"
        print("updating handle at ", url)
        payload = "[\n    {\n        \"index\": 1,\n        \"type\": \"URL\",\n        \"data\": {\n            \"format\": \"string\",\n            \"value\": \"" + hh['view_node'] + "\"\n        }\n    },\n    {\n        \"index\": 100,\n        \"type\": \"HS_ADMIN\",\n        \"data\": {\n            \"format\": \"admin\",\n            \"value\": {\n                \"handle\": \"2286/ASU_ADMIN\",\n                \"index\": 300,\n                \"permissions\": \"111111111111\"\n            }\n        }\n    }\n]"


    else:
        print("already set correctly")
