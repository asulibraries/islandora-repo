import requests

url = "https://keep.lib.asu.edu/api/terms?_format=json"

zeroes = []

i = 0

while True:
    print("PAGE %i" % i)
    response = requests.get(url + "&page=" + str(i))
    data = response.json()
    if len(data) < 1:
        break
    for t in data:
        nc = int(t['node_count'])
        if nc == 0:
            # print(t)
            zeroes.append(t)
    print(len(zeroes))

    string_zeroes = ""
    for z in zeroes:
        if 'tid' in z and z['tid'] != '':
            string_zeroes = string_zeroes + "," + z['tid']


    print(string_zeroes)
    print("continuing...")
    zeroes = []
    i = i + 1

print("done")
