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


def sjoin(x):
    return "||".join(x[x.notnull()].astype(str)) if not x.empty else x


def lenzi(df):
    return len(df.index) == 0


def loc_lookup(atype, astring):
    return astring
    # BASICALLY IGNORING ALL OF THIS BECAUSE PEOPLE DO NOT WANT US TO AUTOMATE THIS
    # print(atype)
    # print(astring)
    if not isinstance(astring, str):  # or math.isnan(astring):
        return None
    global loc_df
    loc_base = "https://id.loc.gov/authorities/"

    if "||" in astring:
        nstring = astring.split("||")
    else:
        nstring = [astring]

    return_strings = []
    print(nstring)

    if atype.strip() == "names":
        # split on the "$$"
        i = 0
        for n in nstring:
            if "$$" in n:
                nameparts = n.split("|")
                nm = nameparts[0]
                roles = nameparts[1]
                roles = nameparts[1].split("$$")
                x = 0
                for r in roles:
                    if x == 0:
                        nstring[i] = nm + "|" + r
                    else:
                        nstring.append(nm + "|" + r)
                    x = x + 1
            i = i + 1

    for n in nstring:
        print(n)
        authority = atype.strip()  # subjects, names
        val_to_query = n.strip()
        val_to_query.replace('"', "")
        lc = loc_df.query(
            'label == "' + val_to_query + '" & authority == "' + authority + '"'
        )
        print(len(lc.index))
        if len(lc.index) > 0:
            if lc.iloc[0].uri:
                return_strings.append(val_to_query + "|" + lc.iloc[0].uri)
            else:
                return_strings.append(val_to_query)
            continue
        else:
            # headers = {"User-Agent": "ASU Library"}
            url = loc_base + authority + "/label/" + quote(val_to_query)
            print(url)
            cmd = 'curl -v --user-agent "asu library" ' + url
            args = shlex.split(cmd)
            prc = subprocess.Popen(
                args, shell=False, stdout=subprocess.PIPE, stderr=subprocess.PIPE
            )
            stdout, stderr = prc.communicate()
            # print(stdout.decode("utf-8"))
            # print(stderr.decode("utf-8"))
            m = re.search(
                "< x-uri:[a-zA-Z:\/\s.0-9]*\\r\\n<", stderr.decode("utf-8"))
            if m is None:
                loc_df = loc_df.append(
                    {"label": val_to_query, "authority": authority, "uri": None},
                    ignore_index=True,
                )
                return_strings.append(val_to_query)
                continue
            uri = m.group(0).replace("< x-uri: ", "").replace("\r\n<", "")
            print(uri)
            loc_df = loc_df.append(
                {"label": val_to_query, "authority": authority, "uri": uri},
                ignore_index=True,
            )

            return_strings.append(val_to_query + "|" + uri)
    if len(return_strings) == 1:
        return return_strings[0]
    else:
        return " || ".join(return_strings)


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
            atts = att_df[att_df['item id'] == int(item_id)]
        else:
            atts = att_df[att_df['attachment id'] == str(att_id)]
        # print(atts)
        for index, a in atts.iterrows():
            mime = a['file mime']
            model = get_model_from_mime(mime)
            # print(model)
            return model
    elif att_count == 0:
        return "Binary"
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
    if media_type == "image" and model == "Image" and field == "image":
        return file_id
    elif media_type == "file" and model == "Image" and field == "binary":
        return file_id
    elif (
        media_type == "document" and model == "Digital Document" and field == "document"
    ):
        return file_id
    elif media_type == "audio" and model == "Audio" and field == "audio":
        return file_id
    elif media_type == "video" and model == "Video" and field == "video":
        return file_id
    elif media_type == "file" and model == "Binary" and field == "binary":
        return file_id
    else:
        return None


def role_splitter(contents):
    temp_contents = []
    split = contents.split("||")
    for pair in split:
        if "$$" in pair:
            name = pair.split("|")[0]
            roles = (pair.split("|")[-1]).split("$$")
            for role in roles:
                temp_contents.append(f"{name}|{role}")
        else:
            temp_contents.append(pair)
    return "||".join(temp_contents)


def main(argv):

    if len(argv) < 3:
        print(sys.stderr)
        sys.exit(1)
    repo_md_file = argv[1]
    att_md_file = argv[2]
    merge_df = pandas.read_csv(repo_md_file)
    att_df = pandas.read_csv(att_md_file)
    att_df.sort_values(by=["item id"])
    att_df = att_df.loc[:, ~att_df.columns.str.contains("^Unnamed")]

    merge_df["Personal Contributors"] = merge_df["Personal Contributors"].astype(
        str)
    merge_df["Personal Contributors"] = merge_df["Personal Contributors"].apply(
        lambda cell: role_splitter(cell) if "$$" in cell else cell
    )

    # print(att_df)
    merge_df["Model"] = merge_df.apply(
        lambda row: get_model(row["Attachment Count"],
                              row["Item ID"], att_df, None),
        axis=1,
    )
    merge_df = merge_df.loc[:, ~merge_df.columns.str.contains("^Unnamed")]
    temp_series = merge_df["History"]
    del merge_df["History"]
    merge_df["Parent Item"] = ""
    att_df["old item id"] = ""
    merge_df["Complex Object Child"] = 0

    # for col in att_df.columns:
    #     print(col)
    #     if col == 'media type':
    #         print("TES")

    att_df["image id"] = att_df.apply(
        lambda row: set_file_id(
            row["file mime"], row["media type"], row["file id"], "image"
        ),
        axis=1,
    )
    att_df["document id"] = att_df.apply(
        lambda row: set_file_id(
            row["file mime"], row["media type"], row["file id"], "document"
        ),
        axis=1,
    )
    att_df["video id"] = att_df.apply(
        lambda row: set_file_id(
            row["file mime"], row["media type"], row["file id"], "video"
        ),
        axis=1,
    )
    att_df["audio id"] = att_df.apply(
        lambda row: set_file_id(
            row["file mime"], row["media type"], row["file id"], "audio"
        ),
        axis=1,
    )
    att_df["generic file id"] = att_df.apply(
        lambda row: set_file_id(
            row["file mime"], row["media type"], row["file id"], "binary"
        ),
        axis=1,
    )
    xcols = ["image id", "document id",
             "video id", "audio id", "generic file id"]
    att_df[xcols] = att_df[xcols].replace(".0", "", regex=False)
    att_df["item id"] = att_df["item id"].astype(str)
    att_df["attachment id"] = att_df["attachment id"].astype(str)
    att_df["attachment id"] = "a_" + att_df["attachment id"]
    # for col in merge_df.columns:
    # print(col)
    #    print(merge_df.iloc[0])
    # print(merge_df[merge_df['Attachment Count'] != 1].size)
    # print(merge_df.size)
    complex_objects = merge_df[merge_df["Attachment Count"] != 1]
    for index, c in complex_objects.iterrows():
        # print(c)
        # print(c['Item ID'])
        if not math.isnan(c["Item ID"]):
            cid = int(c["Item ID"])
            # print(cid)
            if cid > 0:
                cid = str(cid)
                atts = att_df[att_df["item id"] == cid]
                # print(atts)
                for index, a in atts.iterrows():
                    # process description and notes
                    if (
                        a["attachment file access"] == 1
                        or a["attachment file access"] == 2
                    ):
                        a_status = "Public"
                    else:
                        a_status = "Private"
                    # notes = ""
                    # if a["attachment notes"] and not math.isnan(a["attachment notes"]):
                    #     notes = notes + str(a["attachment notes"])
                    # if a["attachment description"] and isinstance(
                    #     a["attachment description"], str
                    # ):  # and not math.isnan(a['attachment description']):
                    #     if notes:
                    #         notes = notes + "|" + a["attachment description"]
                    #     else:
                    #         notes = a["attachment description"]
                    new_row = {
                        "Item ID": str(a["attachment id"]),
                        "Item Title": a["attachment label"],
                        "Notes": a["attachment notes"],
                        "Model": get_model(1, None, att_df, str(a["attachment id"])),
                        "Parent Item": a["item id"],
                        "Visibility": a_status,
                        "System Created": a["file created"],
                        "System Updated": a["file created"],
                        "Attachment Count": 1,
                        "Complex Object Child": 1,
                        "Description": a["attachment description"],
                    }
                    # print("add att")
                    # print(new_row)
                    att_df.at[index, "old item id"] = a["item id"]
                    att_df.at[index, "item id"] = str(a["attachment id"])
                    merge_df = merge_df.append(new_row, ignore_index=True)
        # print(getattr(c, 'Item ID'))
        # print(c['ID'])
        # print(c['Item ID'])
        # if index > 10:
        # exit()
    # print(merge_df.size)
    # print(merge_df.iloc[-1])
    # exit()
    # print(merge_df.query('Attachment Count > 1'))

    # print(merge_df.iloc[0])
    # exit()
    tps = [col for col in merge_df if col.startswith("Topical Subject")]
    if len(tps) < 1:
        merge_df["Topical Subject"] = merge_df["Subjects"]
        tps = [col for col in merge_df if col.startswith("Topical Subject")]
    for x in tps:
        merge_df[x] = merge_df[x].apply(
            lambda row: loc_lookup("subjects", row))
    topics = merge_df[
        merge_df.columns[
            pandas.Series(merge_df.columns).str.startswith("Topical Subject")
        ]
    ]
    merge_df["Topical Subjects"] = topics.apply(lambda row: sjoin(row), axis=1)

    if "Creator" in merge_df:
        merge_df["Creator"] = merge_df.Creator.apply(
            lambda row: loc_lookup("names", row)
        )
        authors = merge_df[
            merge_df.columns[pandas.Series(
                merge_df.columns).str.startswith("Creator")]
        ]
        merge_df["Authors"] = authors.apply(lambda row: sjoin(row), axis=1)
    # merge_df['Contributor'] = merge_df.Contributor.apply(
    # lambda row: loc_lookup("names", row))
    # merge_df['Contributor.1'] = merge_df['Contributor.1'].apply(lambda row: loc_lookup("names", row))
    contribs = merge_df[
        merge_df.columns[pandas.Series(
            merge_df.columns).str.startswith("Contributor")]
    ]
    if contribs.empty:
        contribs = merge_df[
            merge_df.columns[
                pandas.Series(merge_df.columns).str.match(
                    "Personal Contributor([^\s]\.?[0-9]*|$)$"
                )
            ]
        ]
    pci = 0
    # print(contribs)
    for ccc in contribs:
        # print("in personal contributors")
        # print(ccc)
        # exit()
        merge_df[ccc] = merge_df[ccc].apply(
            lambda row: loc_lookup("names", row))
        if "Personal Contributor Role" in merge_df:
            # print("there is a pc role")
            if pci == 0:
                role = "Personal Contributor Role"
            else:
                role = "Personal Contributor Role.%i" % pci
            if role in merge_df:
                merge_df[ccc] = merge_df[[ccc, role]].apply(
                    lambda x: ("|".join(x.map(str) if not x.empty else "")), axis=1
                )
            # merge_df[ccc].replace('None|nan', '', inplace=True)
            pci = pci + 1
    # update contribs
    contribs = merge_df[
        merge_df.columns[pandas.Series(
            merge_df.columns).str.startswith("Contributor")]
    ]
    if contribs.empty:
        contribs = merge_df[
            merge_df.columns[
                pandas.Series(merge_df.columns).str.match(
                    "Personal Contributor([^\s]\.?[0-9]*|$)$"
                )
            ]
        ]

    merge_df["Contributors-Person"] = contribs.apply(
        lambda row: sjoin(row), axis=1
    ).str.replace("\|{0,2}None\|nan", "")

    corp_contribs = merge_df[
        merge_df.columns[
            pandas.Series(merge_df.columns).str.startswith(
                "Institutional contributor")
        ]
    ]
    if corp_contribs.empty:
        corp_contribs = merge_df[
            merge_df.columns[
                pandas.Series(merge_df.columns).str.match(
                    "Institutional Contributor([^\s]\.?[0-9]*|$)$"
                )
            ]
        ]
    # corp_contribs = corp_contribs.apply(lambda row: loc_lookup("names", row))
    cci = 0
    for corpcc in corp_contribs:
        # print(corpcc)
        merge_df[corpcc] = merge_df[corpcc].apply(
            lambda row: loc_lookup("names", row))
        if "Institutional Contributor Role" in merge_df:
            # print("there is a cc role")
            if cci == 0:
                role = "Institutional Contributor Role"
            else:
                role = "Institutional Contributor Role.%i" % cci
            if role in merge_df:
                merge_df[corpcc] = merge_df[[corpcc, role]].apply(
                    lambda x: ("|".join(x.map(str) if not x.empty else "")), axis=1
                )
            cci = cci + 1

    # update corp contribs
    corp_contribs = merge_df[
        merge_df.columns[
            pandas.Series(merge_df.columns).str.startswith(
                "Institutional contributor")
        ]
    ]
    if corp_contribs.empty:
        corp_contribs = merge_df[
            merge_df.columns[
                pandas.Series(merge_df.columns).str.match(
                    "Institutional Contributor([^\s]\.?[0-9]*|$)$"
                )
            ]
        ]

    merge_df["Contributors-Corporate"] = corp_contribs.apply(
        lambda row: sjoin(row), axis=1
    ).str.replace("\|{0,2}None\|nan", "")

    geo_subs = [col for col in merge_df if col.startswith(
        "Geographic Subject")]
    if len(geo_subs) > 0:
        for gs in geo_subs:
            merge_df[gs] = merge_df[gs].apply(
                lambda row: loc_lookup("subjects", row))
        geo_subjects = merge_df[
            merge_df.columns[
                pandas.Series(merge_df.columns).str.startswith(
                    "Geographic Subject")
            ]
        ]
        if not geo_subjects.empty:
            merge_df["Geographic Subjects"] = geo_subjects.apply(
                lambda row: sjoin(row), axis=1
            )

    corp_names = [col for col in merge_df if col.startswith(
        "Corporate Name Subject")]
    if len(corp_names) > 0:
        for cn in corp_names:
            merge_df[cn] = merge_df[cn].apply(
                lambda row: loc_lookup("names", row))
        corp_name_subjects = merge_df[
            merge_df.columns[
                pandas.Series(merge_df.columns).str.startswith(
                    "Corporate Name Subject")
            ]
        ]
        merge_df["Corporate Name Subjects"] = corp_name_subjects.apply(
            lambda row: sjoin(row), axis=1
        )

    pers_names = [col for col in merge_df if col.startswith(
        "Personal Name Subject")]
    if len(pers_names) > 0:
        for cn in pers_names:
            merge_df[cn] = merge_df[cn].apply(
                lambda row: loc_lookup("names", row))
        pers_name_subjects = merge_df[
            merge_df.columns[
                pandas.Series(merge_df.columns).str.startswith(
                    "Personal Name Subject")
            ]
        ]
        merge_df["Personal Name Subjects"] = pers_name_subjects.apply(
            lambda row: sjoin(row), axis=1
        )

    merge_df["History JSON"] = temp_series
    merge_df["History JSON"] = merge_df["History JSON"].apply(
        lambda row: row.replace("\n", "").replace("\r\n", "")
        if not isinstance(row, float)
        else None
    )

    xcols = [
        "image id",
        "document id",
        "video id",
        "audio id",
        "generic file id",
        "file id",
    ]
    # for x_col in xcols:
    x_col = "file id"
    # att_df[x_col] = att_df[x_col].replace(".0", "")
    merge_df['Date Created'] = merge_df['Date Created'].astype(str)
    merge_df['Date Created'] = merge_df['Date Created'].str.replace('.0', "", regex=False)
    att_df[x_col] = att_df[x_col].fillna(-1)
    att_df[x_col] = att_df[x_col].astype("int64")
    att_df[x_col] = att_df[x_col].replace(-1, None)
    att_df[x_col] = att_df[x_col].replace("-1", "")

    merge_df.to_csv(
        "c" + str(int(merge_df.iloc[0]["Collection ID"])) + "_merged_v2.csv"
    )
    att_df.to_csv(
        "data/migration_data/att_file_" + str(int(merge_df.iloc[0]["Collection ID"])) + "_v2.csv")


if __name__ == "__main__":
    main(sys.argv)
