from urllib.parse import quote
import sys
import pandas
import shlex
import subprocess
import re
import math

# example usage merge_repo_and_md.py repo.csv md.csv att_md.csv
cols = ["label", "authority", "uri"]
loc_df = pandas.DataFrame(columns=cols)


def sjoin(x): return '||'.join(
    x[x.notnull()].astype(str)) if not x.empty else x


def lenzi(df): return len(df.index) == 0


def loc_lookup(atype, astring):
    # print(atype)
    # print(astring)
    if not isinstance(astring, str): # or math.isnan(astring):
        return None
    global loc_df
    loc_base = "https://id.loc.gov/authorities/"
    authority = atype.strip() # subjects, names
    val_to_query = astring.strip()
    lc = loc_df.query('label == "' + astring + '" & authority == "' + atype + '"')
    if len(lc.index) > 0:
        return astring + "|" + lc.iloc[0].uri
    else:
        # headers = {"User-Agent": "ASU Library"}
        url = loc_base + authority + "/label/" + quote(val_to_query)
        # print(url)
        cmd = 'curl -v --user-agent "asu library" ' + url
        args = shlex.split(cmd)
        prc = subprocess.Popen(args, shell=False, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        stdout, stderr = prc.communicate()
        # print(stdout.decode("utf-8"))
        # print(stderr.decode("utf-8"))
        m = re.search("< x-uri:[a-zA-Z:\/\s.0-9]*\\r\\n<", stderr.decode("utf-8"))
        if m is None:
            return astring
        uri = m.group(0).replace("< x-uri: ", "").replace("\r\n<", "")
        # print(uri)
        loc_df = loc_df.append(
            {"label": astring, "authority": atype, "uri": uri}, ignore_index=True)

        return astring + "|" + uri


def get_model_from_mime(mime):
    print("mime is %s" % mime)
    if isinstance(mime, float):
        return 0
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
    return model


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
            model = get_model_from_mime(mime)
            # print(model)
            return model
    else:
        # print("Paged Content")
        return "Complex Object"


def set_file_id(mime, media_type, file_id, field):
    model = get_model_from_mime(mime)
    # if isinstance(file_id, float):
        # return
    if math.isnan(file_id):
        return
    file_id = int(file_id)
    file_id = str(file_id)
    print(file_id)
    if media_type == "image" and model == "Image" and field == 'image':
        return file_id
    elif media_type == 'document' and model == "Digital Document" and field == 'document':
        return file_id
    elif media_type == 'audio' and model == 'Audio' and field == 'audio':
        return file_id
    elif media_type == 'video' and model == 'Video' and field == 'video':
        return file_id
    elif media_type == 'file' and model == 'Binary' and field == 'binary':
        return file_id
    else:
        return None


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
    del repo_df["Personal Contributors"]

    merge_df = pandas.merge(left=repo_df, right=md_df, left_on='Item ID', right_on='Legacy System ID', how='right')
    merge_df['Model'] = merge_df.apply(lambda row: get_model(row['Attachment Count'], row['Item ID'], att_df, None), axis=1)
    merge_df = merge_df.loc[:, ~merge_df.columns.str.contains('^Unnamed')]
    temp_series = merge_df["History"]
    del merge_df['History']
    if 'Repository Ingestion Notes' in merge_df:
        del merge_df["Repository Ingestion Notes"]
    merge_df['Parent Item'] = ""
    att_df['old item id'] = ""

    print(repo_df.iloc[1])
    col_id = str(int(repo_df.iloc[1]['Collection ID']))

    # print(merge_df)
    print("about to print merge_df columns")
    for col in merge_df.columns:
        print(col)
    print("end merge df columns")
    #     if col == 'media type':
    #         print("TES")

    att_df['image id'] = att_df.apply(lambda row: set_file_id(
        row['file mime'], row['media type'], row['file id'], 'image'), axis=1)
    att_df['document id'] = att_df.apply(
        lambda row: set_file_id(row['file mime'], row['media type'], row['file id'], 'document'), axis=1)
    att_df['video id'] = att_df.apply(lambda row: set_file_id(
        row['file mime'], row['media type'], row['file id'], 'video'), axis=1)
    att_df['audio id'] = att_df.apply(lambda row: set_file_id(
        row['file mime'], row['media type'], row['file id'], 'audio'), axis=1)
    att_df['generic file id'] = att_df.apply(
        lambda row: set_file_id(row['file mime'], row['media type'], row['file id'], 'binary'), axis=1)
    xcols = ['image id', 'document id',
             'video id', 'audio id', 'generic file id']
    att_df[xcols] = att_df[xcols].replace(".0", "")

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

    tps = [col for col in merge_df if col.startswith('Topical Subject')]
    for x in tps:
        merge_df[x] = merge_df[x].apply(lambda row: loc_lookup("subjects", row))
    topics = merge_df[merge_df.columns[pandas.Series(
        merge_df.columns).str.startswith('Topical Subject')]]
    merge_df['Topical Subjects'] = topics.apply(lambda row: sjoin(row), axis=1)
    if 'Creator' in merge_df:
        merge_df['Creator'] = merge_df.Creator.apply(lambda row: loc_lookup("names", row))
        authors = merge_df[merge_df.columns[pandas.Series(
            merge_df.columns).str.startswith('Creator')]]
        merge_df['Authors'] = authors.apply(lambda row: sjoin(row), axis=1)
    # merge_df['Contributor'] = merge_df.Contributor.apply(
        # lambda row: loc_lookup("names", row))
    # merge_df['Contributor.1'] = merge_df['Contributor.1'].apply(lambda row: loc_lookup("names", row))
    contribs = merge_df[merge_df.columns[pandas.Series(
        merge_df.columns).str.startswith('Contributor')]]
    if contribs.empty:
        contribs = merge_df[merge_df.columns[pandas.Series(
            merge_df.columns).str.match('Personal Contributor([^\s]\.?[0-9]*|$)$')]]
    pci = 0
    print(contribs)
    for ccc in contribs:
        print("in personal contributors")
        print(ccc)
        # exit()
        merge_df[ccc] = merge_df[ccc].apply(lambda row: loc_lookup("names", row))
        if 'Personal Contributor Role' in merge_df:
            print("there is a pc role")
            if pci == 0:
                role = "Personal Contributor Role"
            else:
                role = 'Personal Contributor Role.%i' % pci
            if role in merge_df:
                merge_df[ccc] = merge_df[[ccc, role]].apply(lambda x: ('|'.join(x.map(str) if not x.empty else '')), axis=1)
            # merge_df[ccc].replace('None|nan', '', inplace=True)
            pci = pci+1
    # update contribs
    contribs = merge_df[merge_df.columns[pandas.Series(
        merge_df.columns).str.startswith('Contributor')]]
    if contribs.empty:
        contribs = merge_df[merge_df.columns[pandas.Series(
            merge_df.columns).str.match('Personal Contributor([^\s]\.?[0-9]*|$)$')]]

    merge_df['Contributors-Person'] = contribs.apply(
        lambda row: sjoin(row), axis=1).str.replace('\|{0,2}None\|nan', '')

    corp_contribs = merge_df[merge_df.columns[pandas.Series(merge_df.columns).str.startswith('Corporate contributor')]]
    if corp_contribs.empty:
        corp_contribs = merge_df[merge_df.columns[pandas.Series(
            merge_df.columns).str.match('Corporate Contributor([^\s]\.?[0-9]*|$)$')]]
    # corp_contribs = corp_contribs.apply(lambda row: loc_lookup("names", row))
    cci = 0
    for corpcc in corp_contribs:
        print(corpcc)
        merge_df[corpcc] = merge_df[corpcc].apply(
            lambda row: loc_lookup("names", row))
        if 'Corporate Contributor Role' in merge_df:
            print("there is a cc role")
            if cci == 0:
                role = "Corporate Contributor Role"
            else:
                role = 'Corporate Contributor Role.%i' % cci
            if role in merge_df:
                merge_df[corpcc] = merge_df[[corpcc, role]].apply(
                    lambda x: ('|'.join(x.map(str) if not x.empty else '')), axis=1)
            cci = cci + 1

    # update corp contribs
    corp_contribs = merge_df[merge_df.columns[pandas.Series(
            merge_df.columns).str.startswith('Corporate contributor')]]
    if corp_contribs.empty:
        corp_contribs = merge_df[merge_df.columns[pandas.Series(
            merge_df.columns).str.match('Corporate Contributor([^\s]\.?[0-9]*|$)$')]]

    merge_df['Contributors-Corporate'] = corp_contribs.apply(
        lambda row: sjoin(row), axis=1).str.replace('\|{0,2}None\|nan', '')

    geo_subs = [col for col in merge_df if col.startswith('Geographic Subject')]
    for gs in geo_subs:
        merge_df[gs] = merge_df[gs].apply(
            lambda row: loc_lookup("subjects", row)
        )
    geo_subjects = merge_df[merge_df.columns[pandas.Series(
        merge_df.columns
    ).str.startswith('Geographic Subject')]]
    merge_df['Geographic Subjects'] = geo_subjects.apply(lambda row: sjoin(row), axis=1)

    corp_names = [col for col in merge_df if col.startswith("Corporate Name Subject")]
    for cn in corp_names:
        merge_df[cn] = merge_df[cn].apply(
            lambda row: loc_lookup("names", row)
        )
    corp_name_subjects = merge_df[merge_df.columns[pandas.Series(
        merge_df.columns
    ).str.startswith('Corporate Name Subject')]]
    merge_df['Corporate Name Subjects'] = corp_name_subjects.apply(
        lambda row: sjoin(row), axis=1)

    merge_df['History JSON'] = temp_series
    merge_df['History JSON'] = merge_df['History JSON'].apply(lambda row: row.replace(
        '\n', '').replace('\r\n', '') if not isinstance(row, float) else None)

    xcols = ['image id', 'document id', 'video id',
                'audio id', 'generic file id', 'file id']
    # for x_col in xcols:
    x_col = 'file id'
    # att_df[x_col] = att_df[x_col].replace(".0", "")
    att_df[x_col] = att_df[x_col].fillna(-1)
    att_df[x_col] = att_df[x_col].astype('int64')
    att_df[x_col] = att_df[x_col].replace(-1, None)
    att_df[x_col] = att_df[x_col].replace("-1", "")
    # TODO - if created is empty, populate it with the current date/time

    merge_df.to_csv('c' + col_id + '_merged.csv')
    att_df.to_csv('data/migration_data/att_file_' + col_id + '.csv')


if __name__ == '__main__':
    main(sys.argv)
