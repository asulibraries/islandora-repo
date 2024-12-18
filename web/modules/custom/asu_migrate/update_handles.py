import requests
import json

handle_base = "https://handle.asu.edu:8000/api/handles/2286/"
get_headers = {
    'Authorization': 'Basic ' + basic_auth
}
post_headers = get_headers
post_headers['Content-Type'] = 'application/json'
post_headers['Accept'] = 'application/json'


def get_handle(handle_num, hh, attempt=0):
    try:
        get_response = requests.request(
            "GET", handle_base + str(handle_num), headers=get_headers)
        # print(get_response.json())
        get_json = get_response.json()
        hdl_match = False
        if attempt > 2:
            return False
        if 'values' in get_json:
            for v in get_json['values']:
                if v['data']['value'] == hh['view_node']:
                    hdl_match = True
                    return True
            if not hdl_match:
                # print("not a match")
                # print("node url is ", hh['view_node'])
                return False
        elif 'R.I' in handle_num:
            attempt = attempt + 1
            # print("no values")
            # print(get_json)
            handle_num = handle_num.replace("R.I", "R.A")
            # try it with R.A.
            return get_handle(handle_num, hh, attempt)
    except ConnectionError:
        print("connection error for %s" % handle_num)


def process_handle(hh):
    handle_parts = hh['field_handle'].split("/")
    handle_num = handle_parts[-1]
    print(handle_num)
    # print(handle_base + str(handle_num))
    if not get_handle(handle_num, hh):
        # update handle
        if "I.a_" in handle_num:
            print("bad handle value: %s "  % handle_num)
            handle_num.replace("I.a_", "A.")
        url = handle_base + handle_num + "?overwrite=true"
        print("updating handle at ", url)
        payload = json.dumps([
            {
                "index": 1,
                "type": "URL",
                "data": {
                    "format": "string",
                        "value": hh['view_node']
                }
            },
            {
                "index": 100,
                "type": "HS_ADMIN",
                "data": {
                    "format": "admin",
                    "value": {
                        "handle": "2286/ASU_ADMIN",
                        "index": 300,
                        "permissions": "111111111111"
                    }
                }
            }
        ])
        # print(url)
        # print(payload)
        postrequest = requests.request(
            "PUT", url, headers=post_headers, data=payload)
        print(postrequest.status_code)
        # print(postrequest.text)
        # exit()

    else:
        print("already set correctly")


keep_handles = "https://prism.lib.asu.edu/handles?_format=json"
i = 0
all_the_handles = []
while True:
    print("PAGE %i" % i)
    response = requests.request("GET", keep_handles + "&page=" + str(i))
    data = response.json()
    # print(data)
    if len(data) < 1:
        break
    for h in data:
        process_handle(h)
        all_the_handles.append(h)
    i = i + 1

# print(all_the_handles)
print(len(all_the_handles))


# for hh in all_the_handles:
#     process_handle(hh)
