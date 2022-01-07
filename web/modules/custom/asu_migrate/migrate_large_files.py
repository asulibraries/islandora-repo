import pandas
import sys
import os
import shutil

def clean_up_fname(fname):
    fname = fname.replace(" ", "_")
    to_replace_with_nothing = ["'", ",", ")", "(", "&", "[", "]"]
    for trwn in to_replace_with_nothing:
        fname = fname.replace(trwn, "")
    return fname

def main(argv):
    drush = "/var/www/html/drupal/vendor/bin/drush"

    token = "" #TODO

    if len(argv) < 2:
        print(sys.stderr)
        sys.exit(1)
    large_file_info = argv[1]
    lf_df = pandas.read_csv(large_file_info)
    for index, lf in lf_df.iterrows():
        file_id = lf['file id']
        col_id = lf['collection id']
        site = lf['keep/prism']
        if site == 'K':
            site = "keep"
            froot = "asu_ir"
        elif site == 'P':
            site = "prism"
            froot = "prism"
        else:
            print("unknown site %s" % site)
            exit()
        
        # STEP 1 - rollback previous file migrations
        rollback_cmd = drush+" migrate:rollback c"+col_id+"_file --userid=1 --uri=https://"+site+".lib.asu.edu --idlist="+file_id
        print(rollback_cmd)
        # os.system(rollback_cmd)

        # STEP 2 - update the file migration
        config_path = "/var/www/html/drupal/config/2time"
        file_migration_name = "migrate_plus.migration.c"+col_id+"_file.yml"
        if os.path.isdir(config_path):
            for f in os.listdir(config_path):
                os.remove(os.path.join(config_path, f))
        else:
            os.mkdir(config_path)
        file_from = "/var/www/html/drupal/web/modules/custom/asu_migrate/config/install/" + \
            file_migration_name
        file_to = config_path + "/" + file_migration_name
        shutil.copyfile(
             file_from, file_to)
        config_import_cmd = drush+" config:import --partial --source /var/www/html/drupal/config/2time"
        print(config_import_cmd)
        # os.system(config_import_cmd)


        # STEP 3 - curl the file to fedora TODO
        # if this step fails - it must be manually uploaded into S3 via the AWS console
        att_file = "/var/www/html/drupal/web/modules/custom/asu_migrate/data/migration_data/att_file_"+col_id+"_v2.csv"
        att_df = pandas.read_csv(att_file)
        matches = att_df.loc[(att_df['file id'] == file_id)]
        if not matches.empty:
            print("found match")
            if len(matches) == 1:
                for ia, att in matches.iterrows():
                    original_filename = clean_up_fname(att['file original_filename'])
                    original_mime = att['file mime']
            else:
                print("file id %s has too many matches in file %s" %
                    (str(file_id), att_file))
                exit()
        else:
            print("file id %s not found in file %s" %
                    (str(file_id), att_file))
            exit()
        curl_cmd = 'curl -X PUT --upload-file '+original_filename+' -H"Content-Type: '+original_mime+'" -H"Authorization: Bearer ' + \
            token+'" -H"Transfer-Encoding: chunked" "http://10.192.17.250:8080/fcrepo/rest/'+froot+'/c' + \
            col_id+'/'+original_filename+'"'
        print(curl_cmd)
        # os.system(curl_cmd)


        # STEP 4 - migrate import the file
        import_file_cmd = drush+" migrate:import c"+col_id + \
            "_file --userid=1 --uri=https://"+site+".lib.asu.edu --idlist="+file_id
        print(import_file_cmd)
        # os.system(import_file_cmd)

        # STEP 5 - media import update
        import_media_cmd = drush+" migrate:import c"+col_id + \
            "_media --userid=1 --uri=https://"+site+".lib.asu.edu --idlist="+file_id+" --update"
        print(import_media_cmd)
        # os.system(import_media_cmd)


if __name__ == '__main__':
    main(sys.argv)
