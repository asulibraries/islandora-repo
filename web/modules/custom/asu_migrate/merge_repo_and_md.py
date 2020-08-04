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

# example usage merge_repo_and_md.py repo.csv md.csv
cols = ["label", "authority", "uri"]
loc_df = pandas.DataFrame(columns=cols)


def sjoin(x): return '||'.join(x[x.notnull()].astype(str))


def lenzi(df): return len(df.index) == 0


def loc_lookup(atype, astring):
    print(atype)
    print(astring)
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
        print(url)
        cmd = 'curl -v --user-agent "asu library" ' + url
        args = shlex.split(cmd)
        prc = subprocess.Popen(args, shell=False, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        stdout, stderr = prc.communicate()
        # print(stderr.decode("utf-8"))
        m = re.search("< x-uri:[a-zA-Z:\/\s.0-9]*\\r\\n<", stderr.decode("utf-8"))
        if m is None:
            return astring
        uri = m.group(0).replace("< x-uri: ", "").replace("\r\n<", "")
        print(uri)
        loc_df = loc_df.append({"label": astring, "authority": atype, "uri": uri}, ignore_index=True)
        return astring + "|" + uri


def main(argv):

    if len(argv) < 3:
        print(sys.stderr)
        sys.exit(1)
    repo_md_file = argv[1]
    md_md_file = argv[2]
    md_df = pandas.read_csv(md_md_file)
    repo_df = pandas.read_csv(repo_md_file)
    merge_df = pandas.merge(left=repo_df, right=md_df, left_on='Item ID', right_on='ID', how='left')

    # print(merge_df.iloc[0])
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
        # print(col)
    del merge_df["History"] # temp remove history until we decide what to do with it
    # for col in merge_df.columns:
        # print(col)

    # print(merge_df.iloc[0])

    merge_df.to_csv('c' + str(merge_df.iloc[0]['Collection ID']) + '_merged.csv')


if __name__ == '__main__':
    main(sys.argv)
