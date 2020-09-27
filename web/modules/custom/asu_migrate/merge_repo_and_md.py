import requests
from urllib.parse import quote
import sys
import csv
import time
import pandas
import shlex
import subprocess
import re
import math

# example usage merge_repo_and_md.py repo.csv md.csv att_md.csv
cols = ["label", "authority", "uri"]
loc_df = pandas.DataFrame(columns=cols)


def sjoin(x): return '||'.join(x[x.notnull()].astype(str))


def lenzi(df): return len(df.index) == 0


def loc_lookup(atype, astring):
    # print(atype)
    # print(astring)
    if not isinstance(astring, str):# or math.isnan(astring):
        return None
    global loc_df
    loc_base = "https://id.loc.gov/authorities/"
    authority = atype # subjects, names
    val_to_query = astring
    lc = loc_df.query("label == '" + astring + "' & authority == '" + atype + "'")
    if len(lc.index) > 0:
        return astring + "|" + lc.iloc[0].uri
    else:
        headers = {"User-Agent": "ASU Library"}
        url = loc_base + authority + "/label/" + quote(val_to_query)
        # print(url)
        cmd = 'curl -v --user-agent "asu library" ' + url
        args = shlex.split(cmd)
        prc = subprocess.Popen(args, shell=False, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        stdout, stderr = prc.communicate()
        # print(stderr.decode("utf-8"))
        m = re.search("< x-uri:[a-zA-Z:\/\s.0-9]*\\r\\n<", stderr.decode("utf-8"))
        if m is None:
            return astring
        uri = m.group(0).replace("< x-uri: ", "").replace("\r\n<", "")
        # print(uri)
        loc_df = loc_df.append({"label": astring, "authority": atype, "uri": uri}, ignore_index=True)
        return astring + "|" + uri


def get_model(att_count, item_id, att_df, att_id):
    # print("in get model")
    # print(att_count)
    # print(item_id)
    if att_count == 1:
        # print("row is 1")
        if item_id is not None:
            atts = att_df[att_df['item id'] == item_id]
        else:
            atts = att_df[att_df['attachment id'] == att_id]
        # print(atts)
        for index, a in atts.iterrows():
            mime = a['file mime']
            if "image" in mime:
                model = "Image"
            elif "audio" in mime:
                model = "Audio"
            elif "video" in mime:
                model = "Video"
            elif "pdf" in mime:
                model = "Digital Document"
            else:
                model = "Binary"
            # print(model)
            return model
    else:
        # print("Paged Content")
        return "Complex Object"


def set_file_id(model, media_type, file_id):
    # return row['file id']
    print(media_type)
    print(file_id)
    if media_type == "image":
        return file_id
    elif media_type == 'document':
        return file_id
    elif media_type == 'audio':
        return file_id
    elif media_type == 'video':
        return file_id
    elif media_type == 'file':
        return file_id


def main(argv):

    if len(argv) < 4:
        print(sys.stderr)
        sys.exit(1)
    repo_md_file = argv[1]
    md_md_file = argv[2]
    att_md_file = argv[3]
    md_df = pandas.read_csv(md_md_file)
    repo_df = pandas.read_csv(repo_md_file)
    att_df = pandas.read_csv(att_md_file)
    att_df.sort_values(by=['item id'])
    att_df = att_df.loc[:, ~att_df.columns.str.contains('^Unnamed')]
    print(att_df)
    merge_df = pandas.merge(left=repo_df, right=md_df, left_on='Item ID', right_on='ID', how='left')
    merge_df['Model'] = merge_df.apply(lambda row: get_model(row['Attachment Count'], row['Item ID'], att_df, None), axis=1)
    merge_df['Parent Item'] = ""
    att_df['old item id'] = ""

    for col in att_df.columns:
        print(col)
        if col == 'media type':
            print("TES")

    att_df['image id'] = att_df.apply(lambda row: set_file_id(
        'image', row['media type'], row['file id']), axis=1)
    att_df['document id'] = att_df.apply(
        lambda row: set_file_id('document', row['media type'], row['file id']), axis=1)
    att_df['video id'] = att_df.apply(lambda row: set_file_id(
        'video', row['media type'], row['file id']), axis=1)
    att_df['audio id'] = att_df.apply(lambda row: set_file_id(
        'audio', row['media type'], row['file id']), axis=1)
    att_df['generic file id'] = att_df.apply(
        lambda row: set_file_id('generic file', row['media type'], row['file id']), axis=1)

    # for col in merge_df.columns:
        # print(col)
#    print(merge_df.iloc[0])
    print(merge_df[merge_df['Attachment Count'] != 1].size)
    # print(merge_df.size)
    complex_objects = merge_df[merge_df['Attachment Count'] != 1]
    for index, c in complex_objects.iterrows():
        # print(c)
        print(c['Item ID'])
        if (not math.isnan(c['Item ID'])):
            cid = int(c['Item ID'])
            print(cid)
            if cid > 0:
                atts = att_df[att_df['item id']==cid]
                print(atts)
                for index, a in atts.iterrows():
                    # process description and notes
                    if a['attachment file access'] == 1 or a['attachment file access'] == 2:
                        a_status = 'Public'
                    else:
                        a_status = 'Private'
                    notes = ""
                    if a['attachment notes'] and not math.isnan(a['attachment notes']):
                        notes = notes + str(a['attachment notes'])
                    if a['attachment description'] and not math.isnan(a['attachment description']):
                        if notes:
                            notes = notes + "|" + a['attachment description']
                        else:
                            notes = a['attachment description']
                    new_row = {'Item ID': a['attachment id'], 'Item Title': a['attachment label'], 'Notes': a['attachment notes'], 'Model': get_model(1, None, att_df, a['attachment id']), 'Parent Item': a['item id'], 'Visibility': a_status, 'Notes': '|'.join(notes), 'System Created': a['file created'], 'System Updated': a['file created'], 'Attachment Count': 1}
                    print("add att")
                    att_df.at[index, 'old item id'] = a['item id']
                    att_df.at[index, 'item id'] = a['attachment id']
                    merge_df = merge_df.append(new_row, ignore_index=True)

        # print(getattr(c, 'Item ID'))
        # print(c['ID'])
        # print(c['Item ID'])
    # print(merge_df.size)
    # print(merge_df.iloc[-1])
    # exit()
    # print(merge_df.query('Attachment Count > 1'))

    # print(merge_df.iloc[0])
    # exit()
    tps = [col for col in merge_df if col.startswith('Topical Subject')]
    for x in tps:
        merge_df[x] = merge_df[x].apply(lambda row: loc_lookup("subjects", row))
    topics = merge_df[merge_df.columns[pandas.Series(
        merge_df.columns).str.startswith('Topical Subject')]]
    merge_df['Topical Subjects'] = topics.apply(lambda row: sjoin(row), axis=1)
    merge_df['Creator'] = merge_df.Creator.apply(lambda row: loc_lookup("names", row))
    authors = merge_df[merge_df.columns[pandas.Series(
        merge_df.columns).str.startswith('Creator')]]
    merge_df['Authors'] = authors.apply(lambda row: sjoin(row), axis=1)
    merge_df['Contributor'] = merge_df.Contributor.apply(
        lambda row: loc_lookup("names", row))
    merge_df['Contributor.1'] = merge_df['Contributor.1'].apply(lambda row: loc_lookup("names", row))
    contribs = merge_df[merge_df.columns[pandas.Series(
        merge_df.columns).str.startswith('Contributor')]]
    merge_df['Contributors-Person'] = contribs.apply(lambda row: sjoin(row), axis=1)
    merge_df['Geographic Subject'] = merge_df['Geographic Subject'].apply(
        lambda row: loc_lookup("subjects", row))
    # for col in merge_df.columns:
    #     print(col)
    merge_df['History JSON'] = merge_df["History"]
    del merge_df["History JSON"]
    del merge_df["History"] # temp remove history until we decide what to do with it
    del merge_df["Repository Ingestion Notes"]

    merge_df.to_csv('c' + str(int(merge_df.iloc[0]['Collection ID'])) + '_merged.csv')
    att_df.to_csv('data/migration_data/att_file_' +
                  str(int(merge_df.iloc[0]['Collection ID'])) + '.csv')

if __name__ == '__main__':
    main(sys.argv)
