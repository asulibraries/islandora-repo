import sys
import csv
import time
import xml.etree.ElementTree as ET
import codecs


encoder = codecs.getincrementalencoder("utf-8")()


def usage():
    """Print a usage statement for this script."""
    print('Transforms MARC XML into CSV')
    print('Usage:')
    print('  marc_xml_to_csv.py <input file>')
    print('Where:')
    print('   <input file>   marc xml file')


def decode(string_here):
    return encoder.encode(string_here)


def main(argv):
    if len(argv) < 2:
        usage()
        sys.exit(1)
    input_file = argv[1]
    xmltree = ET.parse(input_file)
    rows = []
    outfile = 'xml-to-csv-' + time.strftime("%Y%m%d%H%M%S") + '.csv'
    for record in xmltree.findall('record'):
        row = {'creator': [], 'title': []}
        row['leader'] = record.find('leader').text
        for cf in record.findall('controlfield'):
            # do something with the controlfields
            print("")
        for df in record.findall('datafield'):
            # do something with datafields
            if df.get("tag") == "100":
                # creator
                tc = ''
                for sf in df.findall('subfield'):
                    print(sf.text)
                    if sf.get('code') == 'a':
                        tc += decode(sf.text)
                    if sf.get('code') == '0':
                        tc += decode('(' + sf.text + ')')
                        # TODO strip leading "(uri) "
                        # TODO add subfield e for the role
                row['creator'].append(tc)
            if df.get('tag') == "245":
                # title
                for sf in df.findall('subfield'):
                    if sf.get('code') == 'a':
                        row['title'].append(decode(sf.text))
                        # TODO strip trailing " /"
                        # TODO handle subfield b for the subtitle
            if df.get('tag') == "260":
                # publication data
                for sf in df.findall('subfield'):
                    if sf.get('code') == 'c':
                        row['date_published'] = sf.text
                # TODO make sure we don't have any other subfields here typically
            if df.get('tag') == '300':
                # extent
                for sf in df.findall('subfield'):
                    if sf.get('code') == 'a':
                        row['extent'] = sf.text
                        # TODO strip 1 online resource()?
            # 264 Publication/Copyright notice - subfield a Tempe, Arizona; subfield b ASU; subfield c 2019
            # 336 content type, subfield a text, subfield b txt, subfield 2 rdacontent
            # 337 media type, subfield a computer, subfield b c, subfield 2 rdamedia
            # 338 carrier type, subfield a online resource, subfield b cr, subfield 2 rdaarrier
            # 502 dissertation note, ie subfield a has Thesis (Ph.D.)--Arizona State University, 2019. - contains type of degree and year OR separated into parts: subfield b has degree, subfield c has ASU, subfield d has year
            # 504 bibliography - contains page #s of bibliographic content in the thesis
            # 520 description (in subfield a), has ind1="3"
            # 588 source of description note - "Viewed on February 3rd, 2020."
            # 590 local note - proquest subject code
            # 650 topical subjects - subfield a contains string, subfield 0 contains URI
            # 655 genre/form - subfield a contains term "Academic theses.", subfield 2 contains source ie lcgft, subfield 0 contains uri
            # 653 uncontrolled index term - appear subject like, not sure what to do with these
            # 690 local topical subject - contains subfield a "Dissertations, Academic", subfield z ASU
            # 856 electronic location - subfield u handle
            # 949 local data - looks like a call #
            # 001 control number - looks like MMSID
            # 005 date and time of latest transaction, probably last edit
            # 006 fixed length material characteristics
            # 007 physical description fixed field
            # 008 general info fixed length
            # 035 subfield a OCLC number, subfield 0 URI
            # 040 cataloging source - AZS subfield a, eng/rda subfield e
            # 049 local holdings - ASZO subfield a
        for x, y in row.items():
            if isinstance(y, list):
                nx = "|".join(y)
                row[x] = nx
        print(row)
        rows.append(row)
    with open(outfile, 'w') as output:
        fieldnames = ["creator", "title", "leader", "date_published", "extent"]
        writer = csv.DictWriter(output, fieldnames=fieldnames)
        for r in rows:
            writer.writerow(r)


if __name__ == '__main__':
    main(sys.argv)
